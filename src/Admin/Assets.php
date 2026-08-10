<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || PostType::TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style( 'woo-campaign-admin-list', WOO_CAMPAIGN_URL . 'assets/css/admin-list.css', [], WOO_CAMPAIGN_VERSION );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'woo-campaign-admin', WOO_CAMPAIGN_URL . 'assets/css/admin.css', [], WOO_CAMPAIGN_VERSION );
		wp_enqueue_script( 'woo-campaign-admin', WOO_CAMPAIGN_URL . 'assets/js/admin-campaign.js', [ 'jquery', 'wc-enhanced-select' ], WOO_CAMPAIGN_VERSION, true );
		wp_localize_script( 'woo-campaign-admin', 'WooCampaignAdminSettings', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'woo_campaign_admin' ),
			'i18n'    => [
				'selectVariations'     => __( 'Select Variations', 'wc-campaign' ),
				'addVariations'        => __( 'Add Selected Variations', 'wc-campaign' ),
				'cancel'               => __( 'Cancel', 'wc-campaign' ),
				'noVariationsFound'    => __( 'No variations found for this product.', 'wc-campaign' ),
				'variableProduct'      => __( 'Variable product', 'wc-campaign' ),
				'chooseVariationsHelp' => __( 'Choose the variations you want to sell in this campaign and set their Campaign Prices.', 'wc-campaign' ),
				'selectAll'            => __( 'Select all', 'wc-campaign' ),
				'selected'             => __( 'selected', 'wc-campaign' ),
				'variation'            => __( 'Variation', 'wc-campaign' ),
				'wooPrice'             => __( 'Woo price', 'wc-campaign' ),
				'campaignPrice'        => __( 'Campaign price', 'wc-campaign' ),
				'stock'                => __( 'Stock', 'wc-campaign' ),
				'alreadyAdded'         => __( 'Already added', 'wc-campaign' ),
				'saves'                => __( 'Save', 'wc-campaign' ),
				'networkError'         => __( 'Unable to load product information. Please try again.', 'wc-campaign' ),
			],
		] );
	}
}
