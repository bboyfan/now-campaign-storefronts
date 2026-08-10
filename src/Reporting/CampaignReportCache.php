<?php

namespace WooCampaign\Reporting;

use WooCampaign\Order\OrderAttribution;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignReportCache {
	private const TTL = 15;

	public function __construct( private CampaignDetailedReportService $reports ) {}

	public function register(): void {
		add_action( 'woocommerce_checkout_order_created', [ $this, 'invalidateOrder' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'invalidateOrderId' ], 10, 4 );
		add_action( 'woocommerce_payment_complete', [ $this, 'invalidateOrderId' ] );
		add_action( 'woocommerce_order_refunded', [ $this, 'invalidateOrderId' ], 10, 2 );
		add_action( 'woocommerce_refund_created', [ $this, 'invalidateRefund' ], 10, 2 );
	}

	public function get( int $campaignId, bool $force = false ): array {
		$key = $this->cacheKey( $campaignId );
		if ( ! $force ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$snapshot = $this->reports->report( $campaignId );
		set_transient( $key, $snapshot, self::TTL );
		return $snapshot;
	}

	public function invalidateCampaign( int $campaignId ): void {
		if ( $campaignId > 0 ) {
			delete_transient( $this->cacheKey( $campaignId ) );
		}
	}

	public function invalidateOrder( \WC_Order $order ): void {
		foreach ( $this->campaignIdsForOrder( $order ) as $campaignId ) {
			$this->invalidateCampaign( $campaignId );
		}
	}

	public function invalidateOrderId( int $orderId ): void {
		$order = wc_get_order( $orderId );
		if ( $order instanceof \WC_Order ) {
			$this->invalidateOrder( $order );
		}
	}

	public function invalidateRefund( int $refundId ): void {
		$refund = wc_get_order( $refundId );
		if ( ! $refund instanceof \WC_Order_Refund ) {
			return;
		}
		$parentId = $refund->get_parent_id();
		if ( $parentId > 0 ) {
			$this->invalidateOrderId( $parentId );
		}
	}

	private function campaignIdsForOrder( \WC_Order $order ): array {
		$ids = [];
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$campaignId = absint( $item->get_meta( OrderAttribution::CAMPAIGN_ID, true ) );
			if ( $campaignId > 0 ) {
				$ids[ $campaignId ] = true;
			}
		}
		return array_map( 'intval', array_keys( $ids ) );
	}

	private function cacheKey( int $campaignId ): string {
		return 'woo_campaign_report_' . $campaignId;
	}
}
