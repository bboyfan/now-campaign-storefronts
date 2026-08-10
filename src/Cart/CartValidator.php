<?php

namespace WooCampaign\Cart;

use WooCampaign\Pricing\CampaignPriceResolver;
use WooCampaign\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CartValidator {
	public function __construct(
		private CampaignPriceResolver $resolver,
		private ProductAdapter $products,
	) {}

	public function register(): void {
		add_action( 'woocommerce_check_cart_items', [ $this, 'validate' ] );
	}

	public function validate(): void {
		if ( ! WC()->cart ) {
			return;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( empty( $item['_woo_campaign_product_id'] ) ) {
				continue;
			}
			$campaignProduct = $this->resolver->resolveFromCartItem( $item );
			if ( ! $campaignProduct ) {
				wc_add_notice( __( 'A campaign item in your cart is no longer available at its campaign terms. Please remove it before checkout.', 'wc-campaign' ), 'error' );
				continue;
			}
			$product = $this->products->get( $campaignProduct->saleableId() );
			if ( ! $product || ! $this->products->isPurchasable( $product ) ) {
				wc_add_notice( __( 'A campaign item in your cart is currently unavailable. Please update your cart before checkout.', 'wc-campaign' ), 'error' );
			}
		}
	}
}
