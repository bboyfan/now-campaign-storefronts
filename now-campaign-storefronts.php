<?php
/**
 * Plugin Name:       NOW Campaign Storefronts for WooCommerce
 * Plugin URI:        https://github.com/bboyfan/now-campaign-storefronts
 * Description:       Build campaign storefronts for WooCommerce with campaign pricing, layouts, attribution, live reports, and protected sharing.
 * Version:           1.4.5
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            NOW Store
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       now-campaign-storefronts
 * WC requires at least: 8.0
 * WC tested up to:   10.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOWCASTF_VERSION', '1.4.5' );
define( 'NOWCASTF_FILE', __FILE__ );
define( 'NOWCASTF_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOWCASTF_URL', plugin_dir_url( __FILE__ ) );

// Public campaign reports are authenticated, cookie-varying pages and must never
// be stored by a full-page cache. Set the standard WordPress cache guard at the
// earliest point available to a normal plugin; the controller repeats this guard
// and emits explicit no-store headers after rewrite resolution.
$nowcastf_request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
$nowcastf_request_path = '' !== $nowcastf_request_uri ? (string) ( wp_parse_url( $nowcastf_request_uri, PHP_URL_PATH ) ?? '' ) : '';
if ( '' !== $nowcastf_request_path && str_contains( '/' . ltrim( $nowcastf_request_path, '/' ), '/campaign-report/' ) && ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}

add_action( 'before_woocommerce_init', static function(): void {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	spl_autoload_register( static function( string $class ): void {
		$prefix = 'Bboyfan\NowCampaignStorefronts\\';
		if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	} );
}

register_activation_hook( __FILE__, [ 'Bboyfan\NowCampaignStorefronts\\Install\\Activator', 'activate' ] );

add_action( 'plugins_loaded', static function(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', static function(): void {
			echo '<div class="notice notice-error"><p><strong>NOW Campaign Storefronts</strong> ' . esc_html__( 'requires WooCommerce to be installed and active.', 'now-campaign-storefronts' ) . '</p></div>';
		} );
		return;
	}
	Bboyfan\NowCampaignStorefronts\Plugin::instance()->init();
} );
