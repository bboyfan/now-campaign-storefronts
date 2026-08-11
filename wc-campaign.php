<?php
/**
 * Plugin Name:       WC Campaign
 * Description:       Build campaign storefronts for WooCommerce with campaign pricing, layouts, attribution, live reports, and protected sharing.
 * Version:           1.4.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WENSZU
 * Author URI:        https://wenszu.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-campaign
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   10.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOO_CAMPAIGN_VERSION', '1.4.1' );
define( 'WOO_CAMPAIGN_FILE', __FILE__ );
define( 'WOO_CAMPAIGN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_CAMPAIGN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'init', static function(): void {
	load_plugin_textdomain( 'wc-campaign', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 0 );

// Public campaign reports are authenticated, cookie-varying pages and must never
// be stored by a full-page cache. Set the standard WordPress cache guard at the
// earliest point available to a normal plugin; the controller repeats this guard
// and emits explicit no-store headers after rewrite resolution.
$requestUri = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
$requestPath = '' !== $requestUri ? (string) ( parse_url( $requestUri, PHP_URL_PATH ) ?? '' ) : '';
if ( '' !== $requestPath && str_contains( '/' . ltrim( $requestPath, '/' ), '/campaign-report/' ) && ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true );
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
		$prefix = 'WooCampaign\\';
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

register_activation_hook( __FILE__, [ 'WooCampaign\\Install\\Activator', 'activate' ] );

add_action( 'plugins_loaded', static function(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', static function(): void {
			echo '<div class="notice notice-error"><p><strong>WC Campaign</strong> ' . esc_html__( 'requires WooCommerce to be installed and active.', 'wc-campaign' ) . '</p></div>';
		} );
		return;
	}
	WooCampaign\Plugin::instance()->init();
} );
