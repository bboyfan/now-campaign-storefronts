<?php

namespace WooCampaign\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OrderAttribution {
	public const CAMPAIGN_ID = '_woo_campaign_id';
	public const CAMPAIGN_PRODUCT_ID = '_woo_campaign_product_id';
	public const CAMPAIGN_PRICE = '_woo_campaign_price';
	public const BASE_PRICE = '_woo_campaign_base_price';
	public const CAMPAIGN_TITLE = '_woo_campaign_title';
	public const CAMPAIGN_SLUG = '_woo_campaign_slug';

	public function register(): void {
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'persist' ], 10, 4 );
	}

	public function persist( \WC_Order_Item_Product $item, string $cartItemKey, array $values, \WC_Order $order ): void {
		if ( empty( $values[ self::CAMPAIGN_ID ] ) ) {
			return;
		}
		$map = [
			self::CAMPAIGN_ID         => absint( $values[ self::CAMPAIGN_ID ] ?? 0 ),
			self::CAMPAIGN_PRODUCT_ID => absint( $values[ self::CAMPAIGN_PRODUCT_ID ] ?? 0 ),
			self::CAMPAIGN_PRICE      => wc_format_decimal( $values[ self::CAMPAIGN_PRICE ] ?? '' ),
			self::BASE_PRICE          => wc_format_decimal( $values[ self::BASE_PRICE ] ?? '' ),
			self::CAMPAIGN_TITLE      => sanitize_text_field( (string) ( $values[ self::CAMPAIGN_TITLE ] ?? '' ) ),
			self::CAMPAIGN_SLUG       => sanitize_title( (string) ( $values[ self::CAMPAIGN_SLUG ] ?? '' ) ),
		];
		foreach ( $map as $key => $value ) {
			if ( '' !== $value && 0 !== $value ) {
				$item->add_meta_data( $key, $value, true );
			}
		}
	}
}
