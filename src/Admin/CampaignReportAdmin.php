<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\PostType;
use WooCampaign\Reporting\CampaignReportShare;
use WooCampaign\Reporting\CampaignReportService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignReportAdmin {
	private const PAGE_SLUG = 'woo-campaign-editor';
	private const NONCE_ACTION = 'woo_campaign_report_admin';

	public function __construct(
		private CampaignReportShare $share,
		private CampaignReportService $reports,
	) {}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 30 );
		add_action( 'wp_ajax_woo_campaign_report_save', [ $this, 'ajaxSave' ] );
		add_action( 'wp_ajax_woo_campaign_report_regenerate', [ $this, 'ajaxRegenerate' ] );
	}

	public function enqueue(): void {
		if ( self::PAGE_SLUG !== sanitize_key( (string) ( $_GET['page'] ?? '' ) ) ) {
			return;
		}
		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( $campaignId <= 0 || PostType::TYPE !== get_post_type( $campaignId ) ) {
			return;
		}

		$summary = $this->reports->report( $campaignId );
		$orders = max( 0, (int) ( $summary['orders'] ?? 0 ) );
		$averageOrder = $orders > 0 ? (float) ( $summary['net_sales'] ?? 0 ) / $orders : 0.0;

		wp_enqueue_style( 'woo-campaign-report-admin', WOO_CAMPAIGN_URL . 'assets/css/campaign-report-admin.css', [ 'woo-campaign-editor' ], WOO_CAMPAIGN_VERSION );
		wp_enqueue_script( 'woo-campaign-report-admin', WOO_CAMPAIGN_URL . 'assets/js/campaign-report-admin.js', [ 'jquery', 'woo-campaign-editor' ], WOO_CAMPAIGN_VERSION, true );
		wp_localize_script(
			'woo-campaign-report-admin',
			'WooCampaignReportAdmin',
			[
				'campaignId' => $campaignId,
				'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'share'      => $this->share->adminState( $campaignId ),
				'summary'    => [
					'campaignSubtotal' => wp_kses_post( wc_price( (float) ( $summary['campaign_subtotal'] ?? 0 ) ) ),
					'pendingOrders'    => (int) ( $summary['pending_orders'] ?? 0 ),
					'refundedUnits'    => (int) ( $summary['refunded_units'] ?? 0 ),
					'averageOrder'     => wp_kses_post( wc_price( $averageOrder ) ),
				],
				'i18n'       => [
					'title'                => __( 'External Report', 'now-campaign-storefronts' ),
					'eyebrow'              => __( 'Live sharing', 'now-campaign-storefronts' ),
					'enabled'              => __( 'Enable external live report', 'now-campaign-storefronts' ),
					'password'             => __( 'Share password', 'now-campaign-storefronts' ),
					'passwordHelp'         => __( 'Administrators can view and copy this password. The public report page and API never expose it. Password strength is not restricted.', 'now-campaign-storefronts' ),
					'passwordLegacyHelp'   => __( 'This password was created before recoverable credentials were added and cannot be restored from its hash. Enter it again and save to make it viewable and copyable in the admin.', 'now-campaign-storefronts' ),
					'link'                 => __( 'Share link', 'now-campaign-storefronts' ),
					'copy'                 => __( 'Copy Link', 'now-campaign-storefronts' ),
					'copyPassword'         => __( 'Copy Password', 'now-campaign-storefronts' ),
					'open'                 => __( 'Open', 'now-campaign-storefronts' ),
					'save'                 => __( 'Save Report Settings', 'now-campaign-storefronts' ),
					'regenerate'           => __( 'Regenerate Link', 'now-campaign-storefronts' ),
					'regenerateHelp'       => __( 'Regenerating the link immediately invalidates the old URL.', 'now-campaign-storefronts' ),
					'saved'                => __( 'Report settings saved.', 'now-campaign-storefronts' ),
					'copied'               => __( 'Link copied.', 'now-campaign-storefronts' ),
					'passwordCopied'       => __( 'Password copied.', 'now-campaign-storefronts' ),
				],
			]
		);
	}

	public function ajaxSave(): void {
		$this->guard();
		$campaignId = absint( $_POST['campaign_id'] ?? 0 );
		$enabled = ! empty( $_POST['enabled'] );
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$result = $this->share->save( $campaignId, $enabled, $password );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
		}
		wp_send_json_success( $result );
	}

	public function ajaxRegenerate(): void {
		$this->guard();
		$campaignId = absint( $_POST['campaign_id'] ?? 0 );
		$result = $this->share->regenerate( $campaignId );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
		}
		wp_send_json_success( $result );
	}

	private function guard(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'now-campaign-storefronts' ) ], 403 );
		}
	}
}
