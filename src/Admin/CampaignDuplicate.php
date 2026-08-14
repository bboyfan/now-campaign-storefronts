<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\CampaignDuplicator;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignDuplicate {
	private const NONCE_ACTION = 'woo_campaign_duplicate';
	private const ADMIN_POST_ACTION = 'woo_campaign_duplicate';
	private const PAGE_SLUG = 'woo-campaign-editor';

	public function __construct( private CampaignDuplicator $duplicator ) {}

	public function register(): void {
		add_filter( 'post_row_actions', [ $this, 'rowAction' ], 10, 2 );
		add_action( 'admin_post_' . self::ADMIN_POST_ACTION, [ $this, 'handle' ] );
		add_action( 'admin_notices', [ $this, 'notices' ] );
	}

	public function rowAction( array $actions, \WP_Post $post ): array {
		if ( PostType::TYPE !== $post->post_type || ! current_user_can( 'manage_woocommerce' ) ) {
			return $actions;
		}
		$url = wp_nonce_url(
			add_query_arg(
				[
					'action'      => self::ADMIN_POST_ACTION,
					'campaign_id' => $post->ID,
				],
				admin_url( 'admin-post.php' )
			),
			self::NONCE_ACTION . '_' . $post->ID
		);
		$actions['duplicate'] = '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( __( 'Duplicate “%s”', 'now-campaign-storefronts' ), $post->post_title ) ) . '">' . esc_html__( 'Duplicate', 'now-campaign-storefronts' ) . '</a>';
		return $actions;
	}

	public function handle(): void {
		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( $campaignId <= 0 ) {
			$this->redirectWithError( 'invalid_campaign', __( 'Invalid Campaign.', 'now-campaign-storefronts' ) );
		}
		check_admin_referer( self::NONCE_ACTION . '_' . $campaignId );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage campaigns.', 'now-campaign-storefronts' ) );
		}
		$source = get_post( $campaignId );
		if ( ! $source instanceof \WP_Post || PostType::TYPE !== $source->post_type ) {
			$this->redirectWithError( 'campaign_not_found', __( 'Campaign not found.', 'now-campaign-storefronts' ) );
		}

		$newId = $this->duplicator->duplicate( $campaignId );
		if ( is_wp_error( $newId ) ) {
			$this->redirectWithError( $newId->get_error_code(), $newId->get_error_message() );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'                  => self::PAGE_SLUG,
					'campaign_id'           => $newId,
					'woo_campaign_duplicated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function notices(): void {
		if ( ! empty( $_GET['woo_campaign_duplicated'] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'Campaign duplicated.', 'now-campaign-storefronts' ) );
		}
		if ( ! empty( $_GET['woo_campaign_duplicate_error'] ) ) {
			$message = sanitize_text_field( wp_unslash( $_GET['woo_campaign_duplicate_error'] ) );
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
	}

	private function redirectWithError( string $code, string $message ): never {
		error_log( 'NOW Campaign Storefronts duplicate rejected: ' . $code . ' — ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		wp_safe_redirect(
			add_query_arg(
				[
					'post_type'                     => PostType::TYPE,
					'woo_campaign_duplicate_error'  => rawurlencode( $message ),
				],
				admin_url( 'edit.php' )
			)
		);
		exit;
	}
}
