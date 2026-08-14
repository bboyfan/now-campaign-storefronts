<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\CampaignService;
use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;
use WooCampaign\CampaignProduct\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignList {
	public function __construct(
		private CampaignService $campaigns,
		private Repository $products,
	) {}

	public function register(): void {
		add_filter( 'manage_' . PostType::TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . PostType::TYPE . '_posts_custom_column', [ $this, 'renderColumn' ], 10, 2 );
	}

	public function columns( array $columns ): array {
		$result = [];
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( 'title' === $key ) {
				$result['woo_campaign_status'] = __( 'Status', 'now-campaign-storefronts' );
				$result['woo_campaign_schedule'] = __( 'Schedule', 'now-campaign-storefronts' );
				$result['woo_campaign_products'] = __( 'Products', 'now-campaign-storefronts' );
			}
		}
		return $result;
	}

	public function renderColumn( string $column, int $postId ): void {
		if ( 'woo_campaign_status' === $column ) {
			$status = $this->campaigns->statusLabel( $postId );
			echo '<span class="woo-campaign-list-status woo-campaign-list-status-' . esc_attr( sanitize_html_class( $status ) ) . '"><span aria-hidden="true"></span>' . esc_html( $this->statusText( $status ) ) . '</span>';
			return;
		}

		if ( 'woo_campaign_schedule' === $column ) {
			$start = (int) get_post_meta( $postId, Meta::START_AT, true );
			$end = (int) get_post_meta( $postId, Meta::END_AT, true );
			if ( ! $start && ! $end ) {
				echo '<span class="woo-campaign-list-muted">' . esc_html__( 'Always available', 'now-campaign-storefronts' ) . '</span>';
				return;
			}
			if ( $start ) {
				echo '<span class="woo-campaign-list-date"><small>' . esc_html__( 'Start', 'now-campaign-storefronts' ) . '</small>' . esc_html( wp_date( 'M j, Y H:i', $start, wp_timezone() ) ) . '</span>';
			}
			if ( $end ) {
				echo '<span class="woo-campaign-list-date"><small>' . esc_html__( 'End', 'now-campaign-storefronts' ) . '</small>' . esc_html( wp_date( 'M j, Y H:i', $end, wp_timezone() ) ) . '</span>';
			}
			return;
		}

		if ( 'woo_campaign_products' === $column ) {
			$rows = $this->products->forCampaign( $postId );
			$active = 0;
			foreach ( $rows as $row ) {
				if ( 'active' === $row->status ) $active++;
			}
			echo '<strong>' . esc_html( number_format_i18n( count( $rows ) ) ) . '</strong>';
			if ( count( $rows ) !== $active ) {
				echo '<small class="woo-campaign-list-product-detail">' . esc_html( sprintf( __( '%d active', 'now-campaign-storefronts' ), $active ) ) . '</small>';
			}
		}
	}

	private function statusText( string $status ): string {
		$labels = [
			'active'    => __( 'Active', 'now-campaign-storefronts' ),
			'scheduled' => __( 'Scheduled', 'now-campaign-storefronts' ),
			'expired'   => __( 'Ended', 'now-campaign-storefronts' ),
			'archived'  => __( 'Archived', 'now-campaign-storefronts' ),
			'draft'     => __( 'Draft', 'now-campaign-storefronts' ),
			'pending'   => __( 'Pending', 'now-campaign-storefronts' ),
			'private'   => __( 'Private', 'now-campaign-storefronts' ),
		];
		return $labels[ $status ] ?? ucfirst( str_replace( '-', ' ', $status ) );
	}
}
