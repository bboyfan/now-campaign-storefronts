<?php

namespace WooCampaign\Campaign;

use WooCampaign\CampaignProduct\Repository as CampaignProductRepository;
use WooCampaign\CampaignProduct\Table as CampaignProductTable;
use WooCampaign\CampaignSection\Repository as CampaignSectionRepository;
use WooCampaign\CampaignSection\Table as CampaignSectionTable;
use WooCampaign\Reporting\CampaignReportPostType;
use WooCampaign\Reporting\CampaignReportShare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Duplicates a Campaign's complete user configuration while regenerating every
 * globally unique identity (post ID, custom table row IDs, external report key
 * and hidden report password record).
 *
 * Commerce authority is preserved: WooCommerce products, variations,
 * inventory, cart, discounts, orders, refunds, and financial data are never
 * duplicated. Only Campaign context, Campaign Price, attribution
 * configuration, reporting intent, and presentation configuration are copied.
 */
final class CampaignDuplicator {
	public function __construct(
		private CampaignRepository $campaigns,
		private CampaignSectionRepository $sections,
		private CampaignProductRepository $products,
		private CampaignReportShare $share,
	) {}

	public function duplicate( int $sourceId ): int|\WP_Error {
		$source = $this->campaigns->find( $sourceId );
		if ( ! $source ) {
			return new \WP_Error( 'campaign_not_found', __( 'Campaign not found.', 'now-campaign-storefronts' ) );
		}

		global $wpdb;
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return new \WP_Error( 'duplicate_transaction', __( 'Unable to start Campaign duplication.', 'now-campaign-storefronts' ) );
		}

		$newId = 0;
		try {
			$newId = $this->duplicatePost( $source );
			$sectionMap = $this->duplicateSections( $sourceId, $newId );
			$this->duplicateProducts( $sourceId, $newId, $sectionMap );
			$this->duplicateMeta( $sourceId, $newId );
			$this->prepareReportIdentity( $sourceId, $newId );
			if ( false === $wpdb->query( 'COMMIT' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				throw new \RuntimeException( 'Unable to commit Campaign duplication.' );
			}
		} catch ( \Throwable $error ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $newId > 0 ) {
				$this->cleanupNewCampaign( $newId );
			}
			error_log( 'NOW Campaign Storefronts duplicate failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return new \WP_Error(
				'duplicate_failed',
				__( 'Campaign could not be duplicated.', 'now-campaign-storefronts' ),
				[ 'error' => $error->getMessage() ]
			);
		}

		clean_post_cache( $newId );
		do_action( 'woo_campaign_duplicated', $newId, $sourceId );
		return $newId;
	}

	private function duplicatePost( \WP_Post $source ): int {
		$newId = wp_insert_post(
			[
				'post_type'       => PostType::TYPE,
				'post_status'     => 'draft',
				'post_title'      => $source->post_title ?: __( 'Untitled Campaign', 'now-campaign-storefronts' ),
				'post_content'    => $source->post_content,
				'post_excerpt'    => $source->post_excerpt,
				'post_author'     => (int) $source->post_author,
				'menu_order'      => (int) $source->menu_order,
				'comment_status'  => $source->comment_status,
				'ping_status'     => $source->ping_status,
			],
			true
		);
		if ( is_wp_error( $newId ) ) {
			throw new \RuntimeException( $newId->get_error_message() );
		}
		return (int) $newId;
	}

	private function duplicateMeta( int $sourceId, int $newId ): void {
		foreach ( Meta::duplicableKeys() as $key ) {
			if ( ! metadata_exists( 'post', $sourceId, $key ) ) {
				continue;
			}
			$this->updateMetaChecked( $newId, $key, get_post_meta( $sourceId, $key, true ) );
		}

		$this->updateMetaChecked( $newId, Meta::EDITOR_REVISION, 0 );

		$thumbnailId = get_post_thumbnail_id( $sourceId );
		if ( $thumbnailId > 0 ) {
			set_post_thumbnail( $newId, (int) $thumbnailId );
		}

		foreach ( get_object_taxonomies( PostType::TYPE ) as $taxonomy ) {
			$terms = wp_get_object_terms( $sourceId, $taxonomy, [ 'fields' => 'ids' ] );
			if ( is_array( $terms ) && $terms ) {
				wp_set_object_terms( $newId, $terms, $taxonomy );
			}
		}
	}

	private function duplicateSections( int $sourceId, int $newId ): array {
		global $wpdb;
		$table = CampaignSectionTable::name();
		$now = current_time( 'mysql', true );
		$sectionMap = [];

		foreach ( $this->sections->forCampaign( $sourceId ) as $section ) {
			$result = $wpdb->insert(
				$table,
				[
					'campaign_id'    => $newId,
					'title'          => $section->title,
					'description'    => $section->description,
					'image_id'       => $section->imageId,
					'layout'         => $section->layout,
					'status'         => $section->status,
					'display_order'  => $section->displayOrder,
					'title_color'    => $section->titleColor,
					'cta_bg_color'   => $section->ctaBgColor,
					'cta_text_color' => $section->ctaTextColor,
					'copy_color'     => $section->copyColor,
					'created_at'     => $now,
					'updated_at'     => $now,
				],
				[ '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
			);
			if ( false === $result || (int) $wpdb->insert_id <= 0 ) {
				throw new \RuntimeException( 'Unable to create a duplicated Campaign section.' );
			}
			$sectionMap[ $section->id ] = (int) $wpdb->insert_id;
		}

		return $sectionMap;
	}

	private function duplicateProducts( int $sourceId, int $newId, array $sectionMap ): void {
		global $wpdb;
		$table = CampaignProductTable::name();
		$now = current_time( 'mysql', true );
		$fallbackSectionId = $sectionMap ? (int) reset( $sectionMap ) : 0;

		foreach ( $this->products->forCampaign( $sourceId ) as $row ) {
			$sectionId = $sectionMap[ $row->sectionId ] ?? $fallbackSectionId;
			$result = $wpdb->insert(
				$table,
				[
					'campaign_id'    => $newId,
					'section_id'     => $sectionId,
					'product_id'     => $row->productId,
					'variation_id'   => $row->variationId,
					'campaign_price' => $row->campaignPrice,
					'campaign_copy'  => $row->campaignCopy,
					'status'         => $row->status,
					'display_order'  => $row->displayOrder,
					'created_at'     => $now,
					'updated_at'     => $now,
				],
				[ '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
			);
			if ( false === $result || (int) $wpdb->insert_id <= 0 ) {
				throw new \RuntimeException( 'Unable to create a duplicated Campaign product.' );
			}
		}
	}

	/**
	 * Copy the external report intent while creating a fresh report identity.
	 *
	 * The source share key, hidden password record, and legacy credentials are
	 * never shared. When the source share password is recoverable, a new native
	 * WordPress password record and a new share key are created through the
	 * existing report save seam so the duplicate starts with its own identity.
	 */
	private function prepareReportIdentity( int $sourceId, int $newId ): void {
		$enabled = (bool) get_post_meta( $sourceId, Meta::REPORT_ENABLED, true );
		if ( ! $enabled ) {
			$this->updateMetaChecked( $newId, Meta::REPORT_ENABLED, 0 );
			return;
		}

		$password = $this->sourceReportPassword( $sourceId );
		if ( '' === $password ) {
			$this->updateMetaChecked( $newId, Meta::REPORT_ENABLED, 1 );
			return;
		}

		$result = $this->share->save( $newId, true, $password );
		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( $result->get_error_message() );
		}
	}

	/**
	 * Read the source Campaign's share password without mutating the source:
	 * no lazy migration, no identity repair is triggered on the source.
	 */
	private function sourceReportPassword( int $sourceId ): string {
		$postId = absint( get_post_meta( $sourceId, Meta::REPORT_POST_ID, true ) );
		if ( $postId <= 0 ) {
			return '';
		}
		$post = get_post( $postId );
		if ( ! $post instanceof \WP_Post || CampaignReportPostType::TYPE !== $post->post_type ) {
			return '';
		}
		return (string) $post->post_password;
	}

	private function updateMetaChecked( int $newId, string $key, mixed $value ): void {
		$result = update_post_meta( $newId, $key, $value );
		if ( false === $result && ! $this->metaEqual( get_post_meta( $newId, $key, true ), $value ) ) {
			throw new \RuntimeException( 'Unable to duplicate Campaign metadata: ' . $key );
		}
	}

	private function metaEqual( mixed $stored, mixed $expected ): bool {
		if ( is_int( $expected ) || is_bool( $expected ) ) {
			return absint( $stored ) === absint( $expected );
		}
		if ( is_array( $expected ) ) {
			return maybe_serialize( $stored ) === maybe_serialize( $expected );
		}
		return (string) $stored === (string) $expected;
	}

	private function cleanupNewCampaign( int $newId ): void {
		try {
			$this->products->deleteForCampaign( $newId );
			$this->sections->deleteForCampaign( $newId );
			$this->share->deleteForCampaign( $newId );
			wp_delete_post( $newId, true );
		} catch ( \Throwable $error ) {
			error_log( 'NOW Campaign Storefronts duplicate cleanup failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
