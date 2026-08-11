<?php

namespace WooCampaign\Storefront;

use WooCampaign\Cart\AjaxController;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	private bool $localized = false;

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybeEnqueue' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueIsolation' ], 999 );
	}

	public function maybeEnqueue(): void {
		if ( ! is_singular( PostType::TYPE ) ) {
			return;
		}
		// Bricks-owned pages: Bricks controls layout; native assets load only
		// when the user deliberately inserts a native commerce shortcode,
		// which calls enqueue() itself.
		if ( $this->isBricksOwned() ) {
			return;
		}
		$this->enqueue();
	}

	public function enqueueIsolation(): void {
		if ( ! is_singular( PostType::TYPE ) || $this->isBricksOwned() ) {
			return;
		}
		wp_enqueue_style(
			'woo-campaign-commerce-isolation',
			WOO_CAMPAIGN_URL . 'assets/css/commerce-isolation.css',
			[ 'woo-campaign-presentation-v2' ],
			WOO_CAMPAIGN_VERSION
		);
		wp_enqueue_style(
			'woo-campaign-presentation-consistency',
			WOO_CAMPAIGN_URL . 'assets/css/presentation-v2-consistency.css',
			[ 'woo-campaign-commerce-isolation' ],
			WOO_CAMPAIGN_VERSION
		);
	}

	private function isBricksOwned(): bool {
		return 'bricks' === apply_filters( CampaignRenderer::FILTER_PRESENTATION_OWNER, 'native', (int) get_queried_object_id() );
	}

	public function enqueue(): void {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'woo-campaign-storefront', WOO_CAMPAIGN_URL . 'assets/css/storefront.css', [], WOO_CAMPAIGN_VERSION );
		wp_enqueue_style( 'woo-campaign-sections', WOO_CAMPAIGN_URL . 'assets/css/campaign-sections.css', [ 'woo-campaign-storefront' ], WOO_CAMPAIGN_VERSION );
		wp_enqueue_style( 'woo-campaign-quick-order-v2', WOO_CAMPAIGN_URL . 'assets/css/quick-order-v2.css', [ 'woo-campaign-sections' ], WOO_CAMPAIGN_VERSION );
		wp_enqueue_style( 'woo-campaign-presentation-v2', WOO_CAMPAIGN_URL . 'assets/css/presentation-v2-storefront.css', [ 'woo-campaign-quick-order-v2' ], WOO_CAMPAIGN_VERSION );
		wp_enqueue_style( 'woo-campaign-bulk-pricing', WOO_CAMPAIGN_URL . 'assets/css/campaign-bulk-pricing-storefront.css', [ 'woo-campaign-presentation-v2' ], WOO_CAMPAIGN_VERSION );
		wp_enqueue_script( 'woo-campaign-storefront', WOO_CAMPAIGN_URL . 'assets/js/storefront.js', [], WOO_CAMPAIGN_VERSION, true );
		wp_enqueue_script( 'woo-campaign-presentation-v2', WOO_CAMPAIGN_URL . 'assets/js/presentation-v2-storefront.js', [ 'woo-campaign-storefront' ], WOO_CAMPAIGN_VERSION, true );
		if ( ! $this->localized ) {
			wp_localize_script(
				'woo-campaign-storefront',
				'WooCampaignSettings',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => AjaxController::nonce(),
					'i18n'    => [
						'error'         => __( 'Something went wrong. Please try again.', 'wc-campaign' ),
						'added'         => __( 'Added to cart', 'wc-campaign' ),
						'updated'       => __( 'Cart updated', 'wc-campaign' ),
						'removed'       => __( 'Item removed', 'wc-campaign' ),
						'empty'         => __( 'Your cart is empty', 'wc-campaign' ),
						'emptyHelp'     => __( 'Add a campaign item to get started.', 'wc-campaign' ),
						'campaign'      => __( 'Campaign', 'wc-campaign' ),
						'remove'        => __( 'Remove', 'wc-campaign' ),
						'decreaseQty'   => __( 'Decrease quantity', 'wc-campaign' ),
						'increaseQty'   => __( 'Increase quantity', 'wc-campaign' ),
						'quantity'      => __( 'Quantity', 'wc-campaign' ),
						'save'          => __( 'Save %d%', 'wc-campaign' ),
					],
				]
			);
			$this->localized = true;
		}
	}
}
