<?php

namespace Bboyfan\NowCampaignStorefronts\Admin;

use Bboyfan\NowCampaignStorefronts\Campaign\PostType;

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
		wp_enqueue_style( 'nowcastf-admin-list', NOWCASTF_URL . 'assets/css/admin-list.css', [], NOWCASTF_VERSION );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'nowcastf-admin', NOWCASTF_URL . 'assets/css/admin.css', [], NOWCASTF_VERSION );
		wp_enqueue_script( 'nowcastf-admin', NOWCASTF_URL . 'assets/js/admin-campaign.js', [ 'jquery', 'wc-enhanced-select' ], NOWCASTF_VERSION, true );
		wp_localize_script( 'nowcastf-admin', 'BboyfanNowCastfAdminSettings', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nowcastf_admin' ),
			'i18n'    => [
				'selectVariations'     => __( 'Select Variations', 'now-campaign-storefronts' ),
				'addVariations'        => __( 'Add Selected Variations', 'now-campaign-storefronts' ),
				'cancel'               => __( 'Cancel', 'now-campaign-storefronts' ),
				'noVariationsFound'    => __( 'No variations found for this product.', 'now-campaign-storefronts' ),
				'variableProduct'      => __( 'Variable product', 'now-campaign-storefronts' ),
				'chooseVariationsHelp' => __( 'Choose the variations you want to sell in this campaign and set their Campaign Prices.', 'now-campaign-storefronts' ),
				'selectAll'            => __( 'Select all', 'now-campaign-storefronts' ),
				'selected'             => __( 'selected', 'now-campaign-storefronts' ),
				'variation'            => __( 'Variation', 'now-campaign-storefronts' ),
				'wooPrice'             => __( 'Woo price', 'now-campaign-storefronts' ),
				'campaignPrice'        => __( 'Campaign price', 'now-campaign-storefronts' ),
				'stock'                => __( 'Stock', 'now-campaign-storefronts' ),
				'alreadyAdded'         => __( 'Already added', 'now-campaign-storefronts' ),
				'saves'                => __( 'Save', 'now-campaign-storefronts' ),
				'networkError'         => __( 'Unable to load product information. Please try again.', 'now-campaign-storefronts' ),
			],
		] );
	}
}
