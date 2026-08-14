<?php

namespace WooCampaign\Pricing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CartPriceApplier {
	private CampaignBulkPricing $bulkPricing;

	public function __construct(
		private CampaignPriceResolver $resolver,
		?CampaignBulkPricing $bulkPricing = null,
	) {
		$this->bulkPricing = $bulkPricing ?? new CampaignBulkPricing();
	}

	public function register(): void {
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply' ], 1 );
		add_filter( 'woocommerce_get_item_data', [ $this, 'itemData' ], 10, 2 );
	}

	public function apply( \WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$resolvedByKey = [];
		$quantityByCampaign = [];
		foreach ( $cart->get_cart() as $key => $cartItem ) {
			if ( empty( $cartItem['_woo_campaign_product_id'] ) || ! isset( $cartItem['data'] ) || ! $cartItem['data'] instanceof \WC_Product ) {
				continue;
			}
			$resolved = $this->resolver->resolveFromCartItem( $cartItem );
			if ( ! $resolved ) {
				continue;
			}
			$resolvedByKey[ $key ] = $resolved;
			$quantityByCampaign[ $resolved->campaignId ] = ( $quantityByCampaign[ $resolved->campaignId ] ?? 0 ) + max( 0, (int) ( $cartItem['quantity'] ?? 0 ) );
		}

		foreach ( $cart->get_cart() as $key => $cartItem ) {
			if ( empty( $cartItem['_woo_campaign_product_id'] ) || ! isset( $cartItem['data'] ) || ! $cartItem['data'] instanceof \WC_Product ) {
				continue;
			}

			$resolved = $resolvedByKey[ $key ] ?? null;
			if ( $resolved ) {
				$campaignQuantity = (int) ( $quantityByCampaign[ $resolved->campaignId ] ?? 0 );
				$discountPercent = $this->bulkPricing->discountPercentForQuantity( $resolved->campaignId, $campaignQuantity );
				$effectivePrice = $this->bulkPricing->effectivePrice( $resolved->campaignId, (float) $resolved->campaignPrice, $campaignQuantity );
				$price = wc_format_decimal( $effectivePrice, wc_get_price_decimals() );
				$cartItem['data']->set_price( $price );
				$cart->cart_contents[ $key ]['_woo_campaign_price'] = $price;

				if ( $discountPercent > 0 ) {
					$cart->cart_contents[ $key ]['_woo_campaign_bulk_discount_percent'] = wc_format_decimal( $discountPercent, 4 );
					$cart->cart_contents[ $key ]['_woo_campaign_bulk_quantity'] = $campaignQuantity;
				} else {
					unset( $cart->cart_contents[ $key ]['_woo_campaign_bulk_discount_percent'], $cart->cart_contents[ $key ]['_woo_campaign_bulk_quantity'] );
				}
				continue;
			}

			// Keep the last server-issued effective Campaign Price visible for an invalid/expired line.
			// Checkout validation blocks the purchase until the line is removed or refreshed.
			if ( isset( $cartItem['_woo_campaign_price'] ) ) {
				$cartItem['data']->set_price( wc_format_decimal( $cartItem['_woo_campaign_price'] ) );
			}
		}
	}

	public function itemData( array $data, array $cartItem ): array {
		if ( ! empty( $cartItem['_woo_campaign_id'] ) ) {
			$data[] = [
				'key'   => __( 'Campaign', 'now-campaign-storefronts' ),
				'value' => sanitize_text_field( (string) ( $cartItem['_woo_campaign_title'] ?? '#' . absint( $cartItem['_woo_campaign_id'] ) ) ),
			];
		}
		return $data;
	}
}
