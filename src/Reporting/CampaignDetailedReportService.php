<?php

namespace NowCampaignStorefronts\Reporting;

use NowCampaignStorefronts\Order\OrderAttribution;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignDetailedReportService {
	private const ORDER_BATCH_SIZE = 100;

	public function __construct( private CampaignReportService $summary ) {}

	public function report( int $campaignId ): array {
		$summary = $this->summary->report( $campaignId );
		$products = $this->productPerformance( $campaignId );
		$orders = max( 0, (int) ( $summary['orders'] ?? 0 ) );
		$netSales = (float) ( $summary['net_sales'] ?? 0 );

		return [
			'summary'       => array_merge(
				$summary,
				[
					'average_order' => $orders > 0 ? (float) wc_format_decimal( $netSales / $orders, wc_get_price_decimals() ) : 0.0,
				]
			),
			'products'      => $products,
			'calculated_at' => time(),
		];
	}

	private function productPerformance( int $campaignId ): array {
		$paidStatuses = array_values( array_unique( array_merge( wc_get_is_paid_statuses(), [ 'refunded' ] ) ) );
		$rows = [];
		$query = [
			'status'     => $paidStatuses,
			'meta_query' => [
				[
					'key'     => OrderAttribution::CAMPAIGN_ID,
					'value'   => (string) $campaignId,
					'compare' => '=',
				],
			],
		];
		$this->forEachOrderBatch(
			$query,
			function ( $order ) use ( &$rows, $campaignId ): void {
				if ( ! $order instanceof \WC_Order ) {
					return;
				}
				foreach ( $order->get_items( 'line_item' ) as $itemId => $item ) {
					if ( absint( $item->get_meta( OrderAttribution::CAMPAIGN_ID, true ) ) !== $campaignId ) {
						continue;
					}
					$campaignProductId = absint( $item->get_meta( OrderAttribution::CAMPAIGN_PRODUCT_ID, true ) );
					$key = $campaignProductId > 0 ? 'campaign-product-' . $campaignProductId : 'order-item-' . $itemId;
					if ( ! isset( $rows[ $key ] ) ) {
						$rows[ $key ] = [
							'campaign_product_id' => $campaignProductId,
							'name'                => $this->displayName( $item ),
							'units'               => 0,
							'refunded_units'      => 0,
							'net_units'           => 0,
							'net_sales'           => 0.0,
						];
					}

					$quantity = max( 0, (int) $item->get_quantity() );
					$refundQty = abs( (int) $order->get_qty_refunded_for_item( $itemId ) );
					$refund = abs( (float) $order->get_total_refunded_for_item( $itemId ) );
					$lineNet = (float) $item->get_total();

					$rows[ $key ]['units'] += $quantity;
					$rows[ $key ]['refunded_units'] += $refundQty;
					$rows[ $key ]['net_units'] += max( 0, $quantity - $refundQty );
					$rows[ $key ]['net_sales'] += $lineNet - $refund;
				}
			}
		);

		$rows = array_values( $rows );
		foreach ( $rows as &$row ) {
			$row['net_sales'] = (float) wc_format_decimal( $row['net_sales'], wc_get_price_decimals() );
		}
		unset( $row );
		usort( $rows, static fn( array $a, array $b ): int => $b['net_sales'] <=> $a['net_sales'] );

		return $rows;
	}

	private function forEachOrderBatch( array $query, callable $consumer ): void {
		$query['limit'] = self::ORDER_BATCH_SIZE;
		$query['return'] = 'objects';
		$query['orderby'] = 'ID';
		$query['order'] = 'ASC';
		$query['page'] = 1;
		$query['paginate'] = true;

		$firstPage = wc_get_orders( $query );
		$maxPages = is_object( $firstPage ) ? max( 1, (int) ( $firstPage->max_num_pages ?? 0 ) ) : 1;
		$orders = is_object( $firstPage ) && is_array( $firstPage->orders ?? null ) ? $firstPage->orders : [];

		for ( $page = 1; $page <= $maxPages; $page++ ) {
			if ( 1 !== $page ) {
				$query['page'] = $page;
				$query['paginate'] = false;
				$orders = wc_get_orders( $query );
				$orders = is_array( $orders ) ? $orders : [];
			}
			foreach ( $orders as $order ) {
				$consumer( $order );
			}
		}
	}

	private function displayName( \WC_Order_Item_Product $item ): string {
		$name = sanitize_text_field( $item->get_name() );
		$product = $item->get_product();
		$variation = $product instanceof \WC_Product_Variation
			? trim( wp_strip_all_tags( wc_get_formatted_variation( $product, true, false, false ) ) )
			: $this->snapshotVariationLabel( $item );
		$variation = preg_replace( '/\s+/', ' ', $variation );
		if ( $variation !== '' && false === stripos( $name, $variation ) ) {
			$name .= ' · ' . $variation;
		}
		return $name;
	}

	private function snapshotVariationLabel( \WC_Order_Item_Product $item ): string {
		$parent = wc_get_product( $item->get_product_id() );
		$attributeKeys = $parent instanceof \WC_Product ? array_keys( $parent->get_attributes() ) : [];
		$parts = [];
		foreach ( $item->get_formatted_meta_data( '_', true ) as $meta ) {
			$key = (string) ( $meta->key ?? '' );
			$value = trim( wp_strip_all_tags( (string) ( $meta->display_value ?? '' ) ) );
			$isAttribute = in_array( $key, $attributeKeys, true ) || str_starts_with( $key, 'attribute_' ) || str_starts_with( $key, 'pa_' );
			if ( ! $isAttribute || '' === $value ) {
				continue;
			}
			$label = trim( wp_strip_all_tags( (string) ( $meta->display_key ?? $key ) ) );
			$parts[] = '' !== $label ? $label . ': ' . $value : $value;
		}
		return implode( ', ', array_unique( $parts ) );
	}
}
