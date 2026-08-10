<?php

namespace WooCampaign\Reporting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignReportController {
	private const REWRITE_VERSION = '1';
	private const REPORT_PATH = '/campaign-report/';

	public function __construct(
		private CampaignReportShare $share,
		private CampaignReportCache $cache,
	) {}

	public function register(): void {
		// Mark public report requests as dynamic as early as a normal plugin can.
		// This remains useful for origin/page-cache plugins even though the actual
		// password session is now WordPress Core's wp-postpass_* cookie.
		$this->protectCurrentReportRequest();

		add_action( 'init', [ self::class, 'registerRewriteRules' ], 9 );
		add_filter( 'query_vars', [ $this, 'queryVars' ] );
		add_action( 'template_redirect', [ $this, 'dispatch' ], 0 );
		add_action( 'send_headers', [ $this, 'sendDynamicHeaders' ], PHP_INT_MAX );
		add_action( 'admin_init', [ $this, 'maybeFlushRewriteRules' ] );
	}

	public static function registerRewriteRules(): void {
		add_rewrite_rule( '^campaign-report/([^/]+)/data/?$', 'index.php?woo_campaign_report_key=$matches[1]&woo_campaign_report_data=1', 'top' );
		add_rewrite_rule( '^campaign-report/([^/]+)/?$', 'index.php?woo_campaign_report_key=$matches[1]', 'top' );
	}

	public function queryVars( array $vars ): array {
		$vars[] = 'woo_campaign_report_key';
		$vars[] = 'woo_campaign_report_data';
		return $vars;
	}

	public function maybeFlushRewriteRules(): void {
		if ( get_option( 'woo_campaign_report_rewrite_version' ) === self::REWRITE_VERSION ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'woo_campaign_report_rewrite_version', self::REWRITE_VERSION, false );
	}

	public function sendDynamicHeaders(): void {
		if ( ! $this->isCurrentReportRequest() || headers_sent() ) {
			return;
		}
		$this->emitDynamicHeaders();
	}

	public function dispatch(): void {
		$key = sanitize_text_field( (string) get_query_var( 'woo_campaign_report_key' ) );
		if ( $key === '' ) {
			return;
		}

		$campaignId = $this->share->findByKey( $key );
		if ( $campaignId <= 0 || ! $this->share->isEnabled( $campaignId ) ) {
			$this->notFound();
		}

		$reportPost = $this->share->reportPost( $campaignId, true );
		if ( ! $reportPost instanceof \WP_Post || empty( $reportPost->post_password ) ) {
			$this->notFound();
		}

		$this->protectCurrentReportRequest();
		$this->emitDynamicHeaders();

		if ( (bool) get_query_var( 'woo_campaign_report_data' ) ) {
			$this->sendData( $campaignId, $reportPost );
		}

		$authenticated = ! post_password_required( $reportPost );
		$snapshot = $authenticated ? $this->cache->get( $campaignId ) : [];
		$this->renderPage( $campaignId, $key, $reportPost, $authenticated, $snapshot );
	}

	private function sendData( int $campaignId, \WP_Post $reportPost ): void {
		$this->emitDynamicHeaders();
		if ( post_password_required( $reportPost ) ) {
			wp_send_json_error( [ 'message' => __( 'Report authentication required.', 'wc-campaign' ) ], 401 );
		}
		wp_send_json_success( $this->presentSnapshot( $this->cache->get( $campaignId ) ) );
	}

	private function renderPage( int $campaignId, string $key, \WP_Post $reportPost, bool $authenticated, array $snapshot ): void {
		$campaign = get_post( $campaignId );
		if ( ! $campaign instanceof \WP_Post ) {
			$this->notFound();
		}

		$presented = $authenticated ? $this->presentSnapshot( $snapshot ) : [];
		$styleUrl = add_query_arg( 'ver', rawurlencode( WOO_CAMPAIGN_VERSION ), WOO_CAMPAIGN_URL . 'assets/css/campaign-report.css' );
		$scriptUrl = add_query_arg( 'ver', rawurlencode( WOO_CAMPAIGN_VERSION ), WOO_CAMPAIGN_URL . 'assets/js/campaign-report.js' );
		$scriptConfig = $authenticated ? [
			'dataUrl'  => $this->share->urlForKey( $key ) . 'data/',
			'interval' => 15000,
			'i18n'     => [
				'emptyProducts' => __( 'There are no paid campaign product results yet.', 'wc-campaign' ),
				'items'         => __( 'items', 'wc-campaign' ),
			],
		] : [];
		$template = WOO_CAMPAIGN_PATH . 'templates/campaign-report.php';
		if ( ! is_readable( $template ) ) {
			wp_die( esc_html__( 'Campaign report template is unavailable.', 'wc-campaign' ), '', [ 'response' => 500 ] );
		}
		include $template;
		exit;
	}

	private function presentSnapshot( array $snapshot ): array {
		$summary = is_array( $snapshot['summary'] ?? null ) ? $snapshot['summary'] : [];
		$products = is_array( $snapshot['products'] ?? null ) ? $snapshot['products'] : [];
		$moneyKeys = [ 'campaign_subtotal', 'discount', 'refund', 'net_sales', 'average_order' ];
		$formatted = [];
		foreach ( $moneyKeys as $key ) {
			$formatted[ $key ] = wp_kses_post( wc_price( (float) ( $summary[ $key ] ?? 0 ) ) );
		}
		foreach ( $products as &$product ) {
			$product['net_sales_html'] = wp_kses_post( wc_price( (float) ( $product['net_sales'] ?? 0 ) ) );
		}
		unset( $product );

		return [
			'summary'       => $summary,
			'formatted'     => $formatted,
			'products'      => $products,
			'calculated_at' => absint( $snapshot['calculated_at'] ?? time() ),
			'updated_label' => wp_date( get_option( 'time_format' ), absint( $snapshot['calculated_at'] ?? time() ) ),
		];
	}

	private function protectCurrentReportRequest(): void {
		if ( ! $this->isCurrentReportRequest() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	private function isCurrentReportRequest(): bool {
		$requestUri = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
		if ( '' === $requestUri ) {
			return false;
		}
		$path = wp_parse_url( $requestUri, PHP_URL_PATH );
		return is_string( $path ) && str_contains( trailingslashit( $path ), self::REPORT_PATH );
	}

	private function emitDynamicHeaders(): void {
		if ( headers_sent() ) {
			return;
		}
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
		header( 'Vary: Cookie', false );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
		header( 'X-WC-Campaign-Report: dynamic', true );
	}

	private function notFound(): never {
		global $wp_query;
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		$this->emitDynamicHeaders();
		wp_die( esc_html__( 'Campaign report not found.', 'wc-campaign' ), esc_html__( 'Not found', 'wc-campaign' ), [ 'response' => 404 ] );
	}
}
