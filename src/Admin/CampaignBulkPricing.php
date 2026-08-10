<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignBulkPricing {
	private const PAGE_SLUG = 'woo-campaign-editor';
	private const EDITOR_NONCE_ACTION = 'woo_campaign_editor_save';

	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 30 );
		add_action( 'woo_campaign_updated', [ $this, 'persistFromEditorRequest' ], 10, 1 );
	}

	public function enqueue(): void {
		if ( self::PAGE_SLUG !== sanitize_key( (string) ( $_GET['page'] ?? '' ) ) ) {
			return;
		}
		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( $campaignId <= 0 || PostType::TYPE !== get_post_type( $campaignId ) ) {
			return;
		}

		$stored = get_post_meta( $campaignId, Meta::BULK_PRICING, true );
		$config = Meta::sanitizeBulkPricing( is_array( $stored ) ? $stored : [] );

		wp_enqueue_style(
			'woo-campaign-bulk-pricing-admin',
			WOO_CAMPAIGN_URL . 'assets/css/campaign-bulk-pricing-admin.css',
			[ 'woo-campaign-editor' ],
			WOO_CAMPAIGN_VERSION
		);
		wp_enqueue_script(
			'woo-campaign-bulk-pricing-admin',
			WOO_CAMPAIGN_URL . 'assets/js/campaign-bulk-pricing-admin.js',
			[ 'jquery', 'woo-campaign-editor' ],
			WOO_CAMPAIGN_VERSION,
			true
		);
		wp_localize_script(
			'woo-campaign-bulk-pricing-admin',
			'WooCampaignBulkPricing',
			[
				'config' => $config,
				'i18n'   => [
					'eyebrow'                  => __( 'Campaign pricing', 'wc-campaign' ),
					'title'                    => __( 'Campaign Bulk Pricing', 'wc-campaign' ),
					'description'              => __( 'Products and variations in the same campaign are counted together; once a tier is reached, the discount is applied to the Campaign Price for each item.', 'wc-campaign' ),
					'enable'                   => __( 'Enable Campaign Bulk Pricing', 'wc-campaign' ),
					'scope'                    => __( 'Calculation: total quantity of all products and variations in the same campaign', 'wc-campaign' ),
					'baseHelp'                 => __( 'Bulk Pricing adjusts only the Campaign Price; coupons and WDP remain handled later by WooCommerce.', 'wc-campaign' ),
					'noticeTitleLabel'         => __( 'Storefront offer title', 'wc-campaign' ),
					'noticeDescriptionLabel'   => __( 'Storefront offer description', 'wc-campaign' ),
					'defaultNoticeTitle'       => __( 'Campaign mix-and-match savings', 'wc-campaign' ),
					'defaultNoticeDescription' => __( 'Products and variations in this campaign are counted together and discounts apply automatically when a tier is reached.', 'wc-campaign' ),
					'quantity'                 => __( 'Quantity threshold', 'wc-campaign' ),
					'discount'                 => __( 'Discount (%)', 'wc-campaign' ),
					'quantityUnit'             => __( 'items or more', 'wc-campaign' ),
					'addTier'                  => __( 'Add tier', 'wc-campaign' ),
					'removeTier'               => __( 'Remove tier', 'wc-campaign' ),
					'empty'                    => __( 'No pricing tiers are configured yet. Add at least one quantity discount after enabling bulk pricing.', 'wc-campaign' ),
				],
			]
		);
	}

	public function persistFromEditorRequest( int $campaignId ): void {
		if ( ! isset( $_POST['campaign_bulk_pricing_json'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( 'woo_campaign_save_editor' !== sanitize_key( (string) ( $_POST['action'] ?? '' ) ) ) {
			return;
		}
		if ( $campaignId !== absint( $_POST['campaign_id'] ?? 0 ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['woo_campaign_editor_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, self::EDITOR_NONCE_ACTION ) ) {
			return;
		}

		$decoded = json_decode( wp_unslash( (string) $_POST['campaign_bulk_pricing_json'] ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return;
		}
		update_post_meta( $campaignId, Meta::BULK_PRICING, Meta::sanitizeBulkPricing( $decoded ) );
		clean_post_cache( $campaignId );
	}
}
