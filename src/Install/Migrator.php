<?php

namespace NowCampaignStorefronts\Install;

use NowCampaignStorefronts\CampaignProduct\Table as CampaignProductTable;
use NowCampaignStorefronts\CampaignSection\CampaignSection;
use NowCampaignStorefronts\CampaignSection\Table as CampaignSectionTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Migrator {
	public const DB_VERSION = '4';
	private const OPTION_KEY = 'nowcastf_db_version';

	public function maybeMigrate(): void {
		if ( get_option( self::OPTION_KEY ) !== self::DB_VERSION ) {
			$this->migrate();
		}
	}

	public function migrate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$productsTable = CampaignProductTable::name();
		$sectionsTable = CampaignSectionTable::name();
		$charsetCollate = $wpdb->get_charset_collate();

		$productsSql = "CREATE TABLE {$productsTable} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT UNSIGNED NOT NULL,
			section_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT UNSIGNED NOT NULL,
			variation_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			campaign_price DECIMAL(26,8) NOT NULL,
			campaign_copy TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			display_order INT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY campaign_product_variation (campaign_id, product_id, variation_id),
			KEY campaign_id (campaign_id),
			KEY section_id (section_id),
			KEY product_id (product_id),
			KEY variation_id (variation_id),
			KEY status (status)
		) {$charsetCollate};";

		$sectionsSql = "CREATE TABLE {$sectionsTable} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(255) NOT NULL DEFAULT '',
			description LONGTEXT NULL,
			image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			layout VARCHAR(32) NOT NULL DEFAULT 'quick_order',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			display_order INT UNSIGNED NOT NULL DEFAULT 0,
			title_color VARCHAR(32) NULL,
			cta_bg_color VARCHAR(32) NULL,
			cta_text_color VARCHAR(32) NULL,
			copy_color VARCHAR(32) NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id),
			KEY campaign_order (campaign_id, display_order),
			KEY status (status)
		) {$charsetCollate};";

		dbDelta( $productsSql );
		dbDelta( $sectionsSql );
		$this->backfillDefaultSections( $productsTable, $sectionsTable );
		update_option( self::OPTION_KEY, self::DB_VERSION, false );
	}

	private function backfillDefaultSections( string $productsTable, string $sectionsTable ): void {
		global $wpdb;
		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$campaignIds = $wpdb->get_col( "SELECT DISTINCT campaign_id FROM {$productsTable} WHERE section_id = 0" );
		if ( ! $campaignIds ) {
			return;
		}

		$now = current_time( 'mysql', true );
		foreach ( $campaignIds as $rawCampaignId ) {
			$campaignId = absint( $rawCampaignId );
			if ( $campaignId <= 0 ) {
				continue;
			}
			// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sectionId = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$sectionsTable} WHERE campaign_id = %d ORDER BY display_order ASC, id ASC LIMIT 1", $campaignId ) // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
			if ( $sectionId <= 0 ) {
				$wpdb->insert(
					$sectionsTable,
					[
						'campaign_id'   => $campaignId,
						'title'         => '',
						'description'   => '',
						'image_id'      => 0,
						'layout'        => CampaignSection::LAYOUT_QUICK_ORDER,
						'status'        => 'active',
						'display_order' => 0,
						'created_at'    => $now,
						'updated_at'    => $now,
					],
					[ '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' ]
				);
				$sectionId = (int) $wpdb->insert_id;
			}
			if ( $sectionId > 0 ) {
				// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					$wpdb->prepare( "UPDATE {$productsTable} SET section_id = %d WHERE campaign_id = %d AND section_id = 0", $sectionId, $campaignId ) // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				);
			}
		}
	}
}