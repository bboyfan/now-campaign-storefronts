<?php

namespace Bboyfan\NowCampaignStorefronts\Admin;

use Bboyfan\NowCampaignStorefronts\Campaign\CampaignService;
use Bboyfan\NowCampaignStorefronts\Campaign\Meta;
use Bboyfan\NowCampaignStorefronts\Campaign\PostType;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignMetaBox {
	private const NONCE_ACTION = 'nowcastf_save_settings';
	private const NONCE_NAME = 'nowcastf_settings_nonce';

	public function __construct(
		private CampaignReportService $reports,
		private CampaignService $campaigns,
	) {}

	public function register(): void {
		add_action( 'add_meta_boxes_' . PostType::TYPE, [ $this, 'addMetaBoxes' ] );
		add_action( 'save_post_' . PostType::TYPE, [ $this, 'save' ], 10, 2 );
	}

	public function addMetaBoxes(): void {
		add_meta_box( 'nowcastf-settings', __( 'Campaign Settings', 'now-campaign-storefronts' ), [ $this, 'renderSettings' ], PostType::TYPE, 'side', 'high' );
		add_meta_box( 'nowcastf-report', __( 'Campaign Performance', 'now-campaign-storefronts' ), [ $this, 'renderReport' ], PostType::TYPE, 'side', 'default' );
	}

	public function renderSettings( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$start = (int) get_post_meta( $post->ID, Meta::START_AT, true );
		$end = (int) get_post_meta( $post->ID, Meta::END_AT, true );
		$archived = (bool) get_post_meta( $post->ID, Meta::ARCHIVED, true );
		$status = 'auto-draft' === $post->post_status ? 'draft' : $this->campaigns->statusLabel( $post->ID );
		?>
		<div class="nowcastf-admin-status woo-campaign-status-<?php echo esc_attr( sanitize_html_class( $status ) ); ?>">
			<span class="nowcastf-status-dot" aria-hidden="true"></span>
			<div>
				<span class="nowcastf-status-label"><?php echo esc_html( $this->statusText( $status ) ); ?></span>
				<span class="nowcastf-status-help"><?php echo esc_html( $this->statusHelp( $status ) ); ?></span>
			</div>
		</div>

		<div class="nowcastf-admin-field">
			<label for="nowcastf-start"><?php esc_html_e( 'Starts', 'now-campaign-storefronts' ); ?></label>
			<input class="widefat" id="nowcastf-start" type="datetime-local" name="nowcastf_start_at" value="<?php echo esc_attr( $this->formatTimestamp( $start ) ); ?>">
			<span class="description"><?php esc_html_e( 'Leave empty to make it available immediately after publishing.', 'now-campaign-storefronts' ); ?></span>
		</div>

		<div class="nowcastf-admin-field">
			<label for="nowcastf-end"><?php esc_html_e( 'Ends', 'now-campaign-storefronts' ); ?></label>
			<input class="widefat" id="nowcastf-end" type="datetime-local" name="nowcastf_end_at" value="<?php echo esc_attr( $this->formatTimestamp( $end ) ); ?>">
			<span class="description"><?php esc_html_e( 'Leave empty for no automatic end date.', 'now-campaign-storefronts' ); ?></span>
		</div>

		<label class="nowcastf-archive-toggle">
			<input type="checkbox" name="nowcastf_archived" value="1" <?php checked( $archived ); ?>>
			<span>
				<strong><?php esc_html_e( 'Archive campaign', 'now-campaign-storefronts' ); ?></strong>
				<small><?php esc_html_e( 'Archived campaigns cannot be purchased from.', 'now-campaign-storefronts' ); ?></small>
			</span>
		</label>

		<?php if ( 'publish' === $post->post_status ) : ?>
			<div class="nowcastf-admin-public-link">
				<a class="button button-secondary" href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View campaign', 'now-campaign-storefronts' ); ?> <span class="dashicons dashicons-external"></span>
				</a>
			</div>
		<?php endif; ?>
		<?php
	}

	public function renderReport( \WP_Post $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<div class="nowcastf-empty-state"><span class="dashicons dashicons-chart-line"></span><p>' . esc_html__( 'Save the campaign to begin tracking performance.', 'now-campaign-storefronts' ) . '</p></div>';
			return;
		}

		$report = $this->reports->report( $post->ID );
		?>
		<div class="nowcastf-performance-primary">
			<span><?php esc_html_e( 'Net sales', 'now-campaign-storefronts' ); ?></span>
			<strong><?php echo wp_kses_post( wc_price( $report['net_sales'] ) ); ?></strong>
			<small><?php esc_html_e( 'After discounts and item refunds', 'now-campaign-storefronts' ); ?></small>
		</div>
		<div class="nowcastf-performance-grid">
			<?php $this->metric( __( 'Paid orders', 'now-campaign-storefronts' ), number_format_i18n( $report['orders'] ) ); ?>
			<?php $this->metric( __( 'Units', 'now-campaign-storefronts' ), number_format_i18n( $report['units'] ) ); ?>
			<?php $this->metric( __( 'Campaign subtotal', 'now-campaign-storefronts' ), wc_price( $report['campaign_subtotal'] ), true ); ?>
			<?php $this->metric( __( 'Discounts', 'now-campaign-storefronts' ), wc_price( $report['discount'] ), true ); ?>
			<?php $this->metric( __( 'Refunds', 'now-campaign-storefronts' ), wc_price( $report['refund'] ), true ); ?>
			<?php $this->metric( __( 'Pending', 'now-campaign-storefronts' ), number_format_i18n( $report['pending_orders'] ) ); ?>
		</div>
		<?php if ( (int) $report['refunded_units'] > 0 ) : ?>
			<p class="nowcastf-performance-note"><?php echo esc_html( sprintf( _n( '%s refunded unit', '%s refunded units', (int) $report['refunded_units'], 'now-campaign-storefronts' ), number_format_i18n( $report['refunded_units'] ) ) ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function save( int $postId, \WP_Post $post ): void {
		if ( wp_is_post_revision( $postId ) || wp_is_post_autosave( $postId ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		update_post_meta( $postId, Meta::START_AT, $this->parseDate( sanitize_text_field( wp_unslash( $_POST['nowcastf_start_at'] ?? '' ) ) ) );
		update_post_meta( $postId, Meta::END_AT, $this->parseDate( sanitize_text_field( wp_unslash( $_POST['nowcastf_end_at'] ?? '' ) ) ) );
		update_post_meta( $postId, Meta::ARCHIVED, isset( $_POST['nowcastf_archived'] ) ? 1 : 0 );
		do_action( 'nowcastf_updated', $postId );
	}

	private function metric( string $label, string $value, bool $allowHtml = false ): void {
		?>
		<div class="nowcastf-performance-metric">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo $allowHtml ? wp_kses_post( $value ) : esc_html( $value ); ?></strong>
		</div>
		<?php
	}

	private function statusText( string $status ): string {
		$labels = [
			'active'    => __( 'Active', 'now-campaign-storefronts' ),
			'scheduled' => __( 'Scheduled', 'now-campaign-storefronts' ),
			'expired'   => __( 'Ended', 'now-campaign-storefronts' ),
			'archived'  => __( 'Archived', 'now-campaign-storefronts' ),
			'draft'     => __( 'Draft', 'now-campaign-storefronts' ),
			'pending'   => __( 'Pending review', 'now-campaign-storefronts' ),
			'private'   => __( 'Private', 'now-campaign-storefronts' ),
		];
		return $labels[ $status ] ?? ucfirst( str_replace( '-', ' ', $status ) );
	}

	private function statusHelp( string $status ): string {
		$labels = [
			'active'    => __( 'Customers can purchase campaign products now.', 'now-campaign-storefronts' ),
			'scheduled' => __( 'The campaign will open at the scheduled start time.', 'now-campaign-storefronts' ),
			'expired'   => __( 'The campaign end time has passed.', 'now-campaign-storefronts' ),
			'archived'  => __( 'Purchasing is disabled until the campaign is restored.', 'now-campaign-storefronts' ),
			'draft'     => __( 'Publish when the campaign is ready to go live.', 'now-campaign-storefronts' ),
		];
		return $labels[ $status ] ?? __( 'Campaign availability follows its publish status and schedule.', 'now-campaign-storefronts' );
	}

	private function parseDate( string $value ): int {
		if ( '' === $value ) {
			return 0;
		}
		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', $value, wp_timezone() );
		return $date instanceof \DateTimeImmutable ? $date->getTimestamp() : 0;
	}

	private function formatTimestamp( int $timestamp ): string {
		return $timestamp > 0 ? wp_date( 'Y-m-d\\TH:i', $timestamp, wp_timezone() ) : '';
	}
}
