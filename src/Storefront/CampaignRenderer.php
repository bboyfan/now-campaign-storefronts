<?php

namespace WooCampaign\Storefront;

use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignRenderer {
	public const FILTER_PRESENTATION_OWNER = 'woo_campaign_storefront_presentation_owner';

	private static bool $renderedProducts = false;
	private static bool $renderedMiniCart = false;
	private static bool $nativeTemplate = false;

	public static function markProductsRendered(): void {
		self::$renderedProducts = true;
	}

	public static function markMiniCartRendered(): void {
		self::$renderedMiniCart = true;
	}

	public static function hasRenderedProducts(): bool {
		return self::$renderedProducts;
	}

	public static function hasRenderedMiniCart(): bool {
		return self::$renderedMiniCart;
	}

	public static function isNativeTemplate(): bool {
		return self::$nativeTemplate;
	}

	public function register(): void {
		// After Bricks (100) so a Bricks content template is resolved before we decide.
		add_filter( 'template_include', [ $this, 'templateInclude' ], 110 );
		add_filter( 'the_content', [ $this, 'appendCampaignCommerce' ], 20 );
		add_action( 'wp_footer', [ $this, 'renderFooterFallback' ], 20 );
	}

	public function templateInclude( string $template ): string {
		if ( ! is_singular( PostType::TYPE ) ) {
			return $template;
		}
		if ( $this->isBricksOwned() ) {
			return $template;
		}
		$native = WOO_CAMPAIGN_PATH . 'templates/single-woo_campaign.php';
		if ( ! is_readable( $native ) ) {
			return $template;
		}
		self::$nativeTemplate = true;
		return $native;
	}

	public function appendCampaignCommerce( string $content ): string {
		if ( self::$nativeTemplate || $this->isBricksOwned() || ! is_singular( PostType::TYPE ) || is_feed() || doing_action( 'wp_head' ) ) {
			return $content;
		}
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		if ( ! self::$renderedProducts && ! has_shortcode( $content, 'woo_campaign_products' ) ) {
			$content .= do_shortcode( '[woo_campaign_products]' );
		}
		if ( ! self::$renderedMiniCart && ! has_shortcode( $content, 'woo_campaign_mini_cart' ) ) {
			$content .= do_shortcode( '[woo_campaign_mini_cart]' );
		}
		return $content;
	}

	public function renderFooterFallback(): void {
		if ( self::$nativeTemplate || $this->isBricksOwned() || ! is_singular( PostType::TYPE ) || is_feed() ) {
			return;
		}
		if ( ! self::$renderedProducts ) {
			echo do_shortcode( '[woo_campaign_products]' );
		}
		if ( ! self::$renderedMiniCart ) {
			echo do_shortcode( '[woo_campaign_mini_cart]' );
		}
	}

	/**
	 * Whether another presentation layer (currently only Bricks) owns this
	 * Campaign page. The Bricks integration flips this via the official
	 * bricks/active_templates hook; core never imports Bricks classes.
	 */
	private function isBricksOwned(): bool {
		return 'bricks' === apply_filters( self::FILTER_PRESENTATION_OWNER, 'native', (int) get_queried_object_id() );
	}
}
