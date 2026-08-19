<?php

namespace NowCampaignStorefronts\Reporting;

use NowCampaignStorefronts\Order\OrderAttribution;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignReportService {
	private const ORDER_BATCH_SIZE = 100;

	public function report( int $campaignId, ?string $after = null, ?string $before = null ): array {
		$paidStatuses = array_values( array_unique( array_merge( wc_get_is_paid_statuses(), [ 'refunded' ] ) ) );
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
		if ( $after || $before ) {
			$query['date_created'] = $this->dateRange( $after, $before );
		}

		$summary = [
			'orders'            => 0,
			'units'             => 0,
			'refunded_units'    => 0,
			'campaign_subtotal' => 0.0,
			'discount'          => 0.0,
			'refund'            => 0.0,
			'net_sales'         => 0.0,
			'pending_orders'    => $this->pendingCount( $campaignId ),
		];

		$this->forEachOrderBatch(
			$query,
			function ( $order ) use ( &$summary, $campaignId ): void {
				if ( ! $order instanceof \WC_Order ) {
					return;
				}
				$matched = false;
				foreach ( $order->get_items( 'line_item' ) as $itemId => $item ) {
					if ( absint( $item->get_meta( OrderAttribution::CAMPAIGN_ID, true ) ) !== $campaignId ) {
						continue;
					}
					$matched = true;
					$quantity = (int) $item->get_quantity();
					$campaignPrice = (float) $item->get_meta( OrderAttribution::CAMPAIGN_PRICE, true );
					$campaignSubtotal = $campaignPrice * $quantity;
					$lineNet = (float) $item->get_total();
					$refund = abs( (float) $order->get_total_refunded_for_item( $itemId ) );
					$refundQty = abs( (int) $order->get_qty_refunded_for_item( $itemId ) );

					$summary['units'] += $quantity;
					$summary['refunded_units'] += $refundQty;
					$summary['campaign_subtotal'] += $campaignSubtotal;
					$summary['discount'] += max( 0, $campaignSubtotal - $lineNet );
					$summary['refund'] += $refund;
					$summary['net_sales'] += $lineNet - $refund;
				}
				if ( $matched ) {
					$summary['orders']++;
				}
			}
		);

		foreach ( [ 'campaign_subtotal', 'discount', 'refund', 'net_sales' ] as $moneyKey ) {
			$summary[ $moneyKey ] = (float) wc_format_decimal( $summary[ $moneyKey ], wc_get_price_decimals() );
		}
		return $summary;
	}

	private function pendingCount( int $campaignId ): int {
		$result = wc_get_orders( [
			'limit'      => 1,
			'return'     => 'ids',
			'paginate'   => true,
			'status'     => [ 'pending', 'on-hold' ],
			'meta_query' => [ [ 'key' => OrderAttribution::CAMPAIGN_ID, 'value' => (string) $campaignId, 'compare' => '=' ] ],
		] );
		return is_object( $result ) ? max( 0, (int) ( $result->total ?? 0 ) ) : 0;
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

	private function dateRange( ?string $after, ?string $before ): string {
		if ( $after && $before ) {
			return $after . '...' . $before;
		}
		return $after ? '>=' . $after : '<=' . $before;
	}
}
