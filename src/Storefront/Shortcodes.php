<?php

namespace WooCampaign\Storefront;

use WooCampaign\Campaign\CampaignContext;
use WooCampaign\Campaign\CampaignRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Shortcodes {
	public function __construct(
		private CampaignRepository $campaigns,
		private CampaignSectionRenderer $sections,
		private BulkPricingNotice $bulkPricingNotice,
		private MiniCart $miniCart,
		private Assets $assets,
	) {}

	public function register(): void {
		add_shortcode( 'woo_campaign_products', [ $this, 'products' ] );
		add_shortcode( 'woo_campaign_mini_cart', [ $this, 'miniCart' ] );
	}

	public function products( array $atts = [] ): string {
		if ( ! doing_action( 'wp_head' ) && ! is_feed() ) {
			CampaignRenderer::markProductsRendered();
		}
		$this->assets->enqueue();
		$atts = shortcode_atts( [ 'campaign_id' => 0 ], $atts, 'woo_campaign_products' );
		$campaignId = absint( $atts['campaign_id'] );
		if ( $campaignId <= 0 ) {
			// Shared context validates the candidate: inside a Bricks template
			// get_the_ID() can be the template post, so fall back to the
			// queried Campaign instead of trusting it blindly.
			$campaignId = CampaignContext::resolveId( (int) get_the_ID() );
		}
		if ( $campaignId <= 0 || ! $this->campaigns->find( $campaignId ) ) {
			return '';
		}
		if ( ! $this->campaigns->isActive( $campaignId ) ) {
			return '<div class="woocommerce-info woo-campaign-unavailable">' . esc_html__( 'This campaign is not currently available.', 'now-campaign-storefronts' ) . '</div>';
		}
		return $this->bulkPricingNotice->render( $campaignId ) . $this->sections->render( $campaignId );
	}

	public function miniCart(): string {
		CampaignRenderer::markMiniCartRendered();
		return $this->miniCart->render();
	}
}
