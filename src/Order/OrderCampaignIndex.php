<?php

namespace Bboyfan\NowCampaignStorefronts\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderCampaignIndex {
	public function register(): void {
		add_action( 'woocommerce_checkout_order_created', [ $this, 'index' ] );
	}

	public function index( \WC_Order $order ): void {
		$ids = [];
		foreach ( $order->get_items() as $item ) {
			$id = absint( $item->get_meta( OrderAttribution::CAMPAIGN_ID, true ) );
			if ( $id > 0 ) {
				$ids[ $id ] = true;
			}
		}
		$order->delete_meta_data( OrderAttribution::CAMPAIGN_ID );
		foreach ( array_keys( $ids ) as $id ) {
			$order->add_meta_data( OrderAttribution::CAMPAIGN_ID, (int) $id, false );
		}
		if ( $ids ) {
			$order->save_meta_data();
		}
	}
}
