<?php

namespace NowCampaignStorefronts\Admin;

use NowCampaignStorefronts\Campaign\Meta;
use NowCampaignStorefronts\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignBulkPricing {
	private const PAGE_SLUG = 'nowcastf-editor';
	private const EDITOR_NONCE_ACTION = 'nowcastf_save_editor';

	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 30 );
		add_action( 'nowcastf_updated', [ $this, 'persistFromEditorRequest' ], 10, 1 );
	}

	public function enqueue(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== sanitize_key( (string) ( $_GET['page'] ?? '' ) ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( $campaignId <= 0 || PostType::TYPE !== get_post_type( $campaignId ) ) {
			return;
		}

		$stored = get_post_meta( $campaignId, Meta::BULK_PRICING, true );
		$config = Meta::sanitizeBulkPricing( is_array( $stored ) ? $stored : [] );

		wp_enqueue_style(
			'nowcastf-bulk-pricing-admin',
			NOWCASTF_URL . 'assets/css/campaign-bulk-pricing-admin.css',
			[ 'nowcastf-editor' ],
			NOWCASTF_VERSION
		);
		wp_enqueue_script(
			'nowcastf-bulk-pricing-admin',
			NOWCASTF_URL . 'assets/js/campaign-bulk-pricing-admin.js',
			[ 'jquery', 'nowcastf-editor' ],
			NOWCASTF_VERSION,
			true
		);
		wp_localize_script(
			'nowcastf-bulk-pricing-admin',
			'NowCastfBulkPricing',
			[
				'config' => $config,
				'i18n'   => [
					'eyebrow'                  => __( 'Campaign pricing', 'now-campaign-storefronts' ),
					'title'                    => __( 'Campaign Bulk Pricing', 'now-campaign-storefronts' ),
					'description'              => __( 'Products and variations in the same campaign are counted together; once a tier is reached, the discount is applied to the Campaign Price for each item.', 'now-campaign-storefronts' ),
					'enable'                   => __( 'Enable Campaign Bulk Pricing', 'now-campaign-storefronts' ),
					'scope'                    => __( 'Calculation: total quantity of all products and variations in the same campaign', 'now-campaign-storefronts' ),
					'baseHelp'                 => __( 'Bulk Pricing adjusts only the Campaign Price; coupons and WDP remain handled later by WooCommerce.', 'now-campaign-storefronts' ),
					'noticeTitleLabel'         => __( 'Storefront offer title', 'now-campaign-storefronts' ),
					'noticeDescriptionLabel'   => __( 'Storefront offer description', 'now-campaign-storefronts' ),
					'defaultNoticeTitle'       => __( 'Campaign mix-and-match savings', 'now-campaign-storefronts' ),
					'defaultNoticeDescription' => __( 'Products and variations in this campaign are counted together and discounts apply automatically when a tier is reached.', 'now-campaign-storefronts' ),
					'quantity'                 => __( 'Quantity threshold', 'now-campaign-storefronts' ),
					'discount'                 => __( 'Discount (%)', 'now-campaign-storefronts' ),
					'quantityUnit'             => __( 'items or more', 'now-campaign-storefronts' ),
					'addTier'                  => __( 'Add tier', 'now-campaign-storefronts' ),
					'removeTier'               => __( 'Remove tier', 'now-campaign-storefronts' ),
					'empty'                    => __( 'No pricing tiers are configured yet. Add at least one quantity discount after enabling bulk pricing.', 'now-campaign-storefronts' ),
				],
			]
		);
	}

	public function persistFromEditorRequest( int $campaignId ): void {
		if ( ! isset( $_POST['campaign_bulk_pricing_json'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( 'nowcastf_save_editor' !== sanitize_key( (string) ( $_POST['action'] ?? '' ) ) ) {
			return;
		}
		if ( $campaignId !== absint( $_POST['campaign_id'] ?? 0 ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['nowcastf_editor_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, self::EDITOR_NONCE_ACTION ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$decoded = json_decode( wp_unslash( (string) $_POST['campaign_bulk_pricing_json'] ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return;
		}
		update_post_meta( $campaignId, Meta::BULK_PRICING, Meta::sanitizeBulkPricing( $decoded ) );
		clean_post_cache( $campaignId );
	}
}
