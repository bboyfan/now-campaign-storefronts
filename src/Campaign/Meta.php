<?php

namespace WooCampaign\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Meta {
	public const START_AT = '_woo_campaign_start_at';
	public const END_AT = '_woo_campaign_end_at';
	public const ARCHIVED = '_woo_campaign_archived';
	public const MEDIA_IDS = '_woo_campaign_media_ids';
	public const DESIGN = '_woo_campaign_design';
	public const BULK_PRICING = '_woo_campaign_bulk_pricing';
	public const EDITOR_REVISION = '_woo_campaign_editor_revision';
	public const REPORT_ENABLED = '_woo_campaign_report_enabled';
	public const REPORT_SHARE_KEY = '_woo_campaign_report_share_key';
	public const REPORT_POST_ID = '_woo_campaign_report_post_id';
	// Legacy custom-auth fields. Kept only so existing private installs can migrate once.
	public const REPORT_PASSWORD_HASH = '_woo_campaign_report_password_hash';
	public const REPORT_PASSWORD_SECRET = '_woo_campaign_report_password_secret';
	public const REPORT_ENABLED_AT = '_woo_campaign_report_enabled_at';

	public function register(): void {
		add_action( 'init', [ $this, 'registerMeta' ] );
	}

	public function registerMeta(): void {
		register_post_meta( PostType::TYPE, self::START_AT, $this->integerArgs() );
		register_post_meta( PostType::TYPE, self::END_AT, $this->integerArgs() );
		register_post_meta( PostType::TYPE, self::EDITOR_REVISION, $this->integerArgs( false ) );
		register_post_meta( PostType::TYPE, self::REPORT_POST_ID, $this->integerArgs( false ) );
		register_post_meta(
			PostType::TYPE,
			self::ARCHIVED,
			[
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => static fn( $value ): bool => (bool) $value,
				'auth_callback'     => static fn(): bool => current_user_can( 'manage_woocommerce' ),
			]
		);
		register_post_meta(
			PostType::TYPE,
			self::MEDIA_IDS,
			[
				'type'              => 'array',
				'single'            => true,
				'default'           => [],
				'show_in_rest'      => false,
				'sanitize_callback' => static function( $value ): array {
					if ( ! is_array( $value ) ) {
						return [];
					}
					$ids = array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
					return array_values( array_filter( $ids, 'wp_attachment_is_image' ) );
				},
				'auth_callback'     => static fn(): bool => current_user_can( 'manage_woocommerce' ),
			]
		);
		register_post_meta(
			PostType::TYPE,
			self::DESIGN,
			[
				'type'              => 'object',
				'single'            => true,
				'default'           => self::sanitizeDesign( [] ),
				'show_in_rest'      => false,
				'sanitize_callback' => static fn( $value ): array => self::sanitizeDesign( $value ),
				'auth_callback'     => static fn(): bool => current_user_can( 'manage_woocommerce' ),
			]
		);
		register_post_meta(
			PostType::TYPE,
			self::BULK_PRICING,
			[
				'type'              => 'object',
				'single'            => true,
				'default'           => self::sanitizeBulkPricing( [] ),
				'show_in_rest'      => false,
				'sanitize_callback' => static fn( $value ): array => self::sanitizeBulkPricing( $value ),
				'auth_callback'     => static fn(): bool => current_user_can( 'manage_woocommerce' ),
			]
		);
	}

	public static function sanitizeDesign( mixed $value ): array {
		$value = is_array( $value ) ? $value : [];
		$contentWidth = absint( $value['content_width'] ?? 0 );
		$contentWidth = $contentWidth > 0 ? max( 800, min( 1600, $contentWidth ) ) : 0;

		return [
			'show_title'    => ! array_key_exists( 'show_title', $value ) || (bool) $value['show_title'],
			'page_bg'       => sanitize_hex_color( (string) ( $value['page_bg'] ?? '' ) ) ?: '',
			'text_color'    => sanitize_hex_color( (string) ( $value['text_color'] ?? '' ) ) ?: '',
			'accent_color'  => sanitize_hex_color( (string) ( $value['accent_color'] ?? '' ) ) ?: '',
			'surface_color' => sanitize_hex_color( (string) ( $value['surface_color'] ?? '' ) ) ?: '',
			'border_color'  => sanitize_hex_color( (string) ( $value['border_color'] ?? '' ) ) ?: '',
			'content_width' => $contentWidth,
		];
	}

	public static function sanitizeBulkPricing( mixed $value ): array {
		$value = is_array( $value ) ? $value : [];
		$tiersByQuantity = [];
		$rawTiers = isset( $value['tiers'] ) && is_array( $value['tiers'] ) ? $value['tiers'] : [];

		foreach ( $rawTiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}
			$minQty = absint( $tier['min_qty'] ?? 0 );
			$discount = (float) wc_format_decimal( $tier['discount_percent'] ?? 0, 4 );
			if ( $minQty < 2 || $discount <= 0 || $discount >= 100 ) {
				continue;
			}
			$tiersByQuantity[ $minQty ] = [
				'min_qty'          => $minQty,
				'discount_percent' => $discount,
			];
		}

		ksort( $tiersByQuantity, SORT_NUMERIC );
		return [
			'enabled'            => ! empty( $value['enabled'] ),
			'notice_title'       => sanitize_text_field( (string) ( $value['notice_title'] ?? '' ) ),
			'notice_description' => sanitize_textarea_field( (string) ( $value['notice_description'] ?? '' ) ),
			'tiers'              => array_values( $tiersByQuantity ),
		];
	}

	private function integerArgs( bool $showInRest = true ): array {
		return [
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => $showInRest,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static fn(): bool => current_user_can( 'manage_woocommerce' ),
		];
	}
}
