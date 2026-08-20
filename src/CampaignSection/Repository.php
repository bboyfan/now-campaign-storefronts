<?php

namespace Bboyfan\NowCampaignStorefronts\CampaignSection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Repository {
	public function find( int $id ): ?CampaignSection {
		global $wpdb;
		$table = Table::name();
		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return is_array( $row ) ? $this->hydrate( $row ) : null;
	}

	public function forCampaign( int $campaignId, bool $activeOnly = false ): array {
		global $wpdb;
		$table = Table::name();
		$sql = "SELECT * FROM {$table} WHERE campaign_id = %d";
		$args = [ $campaignId ];
		if ( $activeOnly ) {
			$sql .= ' AND status = %s';
			$args[] = 'active';
		}
		$sql .= ' ORDER BY display_order ASC, id ASC';
		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
		return array_map( fn( array $row ): CampaignSection => $this->hydrate( $row ), $rows ?: [] );
	}

	public function saveForCampaign( int $campaignId, array $rows ): array {
		global $wpdb;
		$table = Table::name();
		$existing = $this->forCampaign( $campaignId );
		$existingIds = array_fill_keys( array_map( fn( CampaignSection $section ): int => $section->id, $existing ), true );
		$existingById = [];
		foreach ( $existing as $section ) {
			$existingById[ $section->id ] = $section;
		}
		$keptIds = [];
		$keyMap = [];
		$now = current_time( 'mysql', true );

		foreach ( $rows as $position => $row ) {
			$clientKey = sanitize_key( (string) ( $row['client_key'] ?? 'section-' . $position ) );
			$id = absint( $row['id'] ?? 0 );
			$existingSection = $id > 0 && isset( $existingById[ $id ] ) ? $existingById[ $id ] : null;
			$titleColor = array_key_exists( 'title_color', $row )
				? ( sanitize_hex_color( (string) $row['title_color'] ) ?: '' )
				: ( $existingSection ? $existingSection->titleColor : '' );
			$copyColor = array_key_exists( 'copy_color', $row )
				? ( sanitize_hex_color( (string) $row['copy_color'] ) ?: '' )
				: ( $existingSection ? $existingSection->copyColor : '' );
			$ctaBgColor = array_key_exists( 'cta_bg_color', $row )
				? ( sanitize_hex_color( (string) $row['cta_bg_color'] ) ?: '' )
				: ( $existingSection ? $existingSection->ctaBgColor : '' );
			$ctaTextColor = array_key_exists( 'cta_text_color', $row )
				? ( sanitize_hex_color( (string) $row['cta_text_color'] ) ?: '' )
				: ( $existingSection ? $existingSection->ctaTextColor : '' );

			$data = [
				'campaign_id'    => $campaignId,
				'title'          => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
				'description'    => wp_kses_post( (string) ( $row['description'] ?? '' ) ),
				'image_id'       => absint( $row['image_id'] ?? 0 ),
				'layout'         => in_array( (string) ( $row['layout'] ?? '' ), CampaignSection::layouts(), true ) ? (string) $row['layout'] : CampaignSection::LAYOUT_QUICK_ORDER,
				'status'         => 'paused' === (string) ( $row['status'] ?? 'active' ) ? 'paused' : 'active',
				'display_order'  => isset( $row['display_order'] ) ? absint( $row['display_order'] ) : $position,
				'title_color'    => $titleColor,
				'copy_color'     => $copyColor,
				'cta_bg_color'   => $ctaBgColor,
				'cta_text_color' => $ctaTextColor,
				'updated_at'     => $now,
			];

			if ( $id > 0 && isset( $existingIds[ $id ] ) ) {
				$result = $wpdb->update(
					$table,
					$data,
					[ 'id' => $id, 'campaign_id' => $campaignId ],
					[ '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ],
					[ '%d', '%d' ]
				);
				if ( false === $result ) {
					throw new \RuntimeException( 'Unable to update a Campaign section.' );
				}
			} else {
				$data['created_at'] = $now;
				$result = $wpdb->insert(
					$table,
					$data,
					[ '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
				);
				if ( false === $result ) {
					throw new \RuntimeException( 'Unable to create a Campaign section.' );
				}
				$id = (int) $wpdb->insert_id;
			}

			if ( $id > 0 ) {
				$keptIds[ $id ] = true;
				$keyMap[ $clientKey ] = $id;
			}
		}

		foreach ( array_keys( $existingIds ) as $existingId ) {
			if ( ! isset( $keptIds[ $existingId ] ) ) {
				$result = $wpdb->delete( $table, [ 'id' => $existingId, 'campaign_id' => $campaignId ], [ '%d', '%d' ] );
				if ( false === $result ) {
					throw new \RuntimeException( 'Unable to remove Campaign sections.' );
				}
			}
		}

		return $keyMap;
	}

	public function updateDesign( int $id, int $campaignId, array $design ): void {
		global $wpdb;
		$result = $wpdb->update(
			Table::name(),
			[
				'title_color'    => sanitize_hex_color( (string) ( $design['title_color'] ?? '' ) ) ?: '',
				'copy_color'     => sanitize_hex_color( (string) ( $design['copy_color'] ?? '' ) ) ?: '',
				'cta_bg_color'   => sanitize_hex_color( (string) ( $design['cta_bg_color'] ?? '' ) ) ?: '',
				'cta_text_color' => sanitize_hex_color( (string) ( $design['cta_text_color'] ?? '' ) ) ?: '',
				'updated_at'     => current_time( 'mysql', true ),
			],
			[ 'id' => $id, 'campaign_id' => $campaignId ],
			[ '%s', '%s', '%s', '%s', '%s' ],
			[ '%d', '%d' ]
		);
		if ( false === $result ) {
			throw new \RuntimeException( 'Unable to update Campaign section design.' );
		}
	}

	public function createDefault( int $campaignId ): int {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$result = $wpdb->insert(
			Table::name(),
			[
				'campaign_id'    => $campaignId,
				'title'          => '',
				'description'    => '',
				'image_id'       => 0,
				'layout'         => CampaignSection::LAYOUT_QUICK_ORDER,
				'status'         => 'active',
				'display_order'  => 0,
				'title_color'    => '',
				'copy_color'     => '',
				'cta_bg_color'   => '',
				'cta_text_color' => '',
				'created_at'     => $now,
				'updated_at'     => $now,
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
		if ( false === $result ) {
			throw new \RuntimeException( 'Unable to create the default Campaign section.' );
		}
		return (int) $wpdb->insert_id;
	}

	public function deleteForCampaign( int $campaignId ): void {
		global $wpdb;
		$result = $wpdb->delete( Table::name(), [ 'campaign_id' => $campaignId ], [ '%d' ] );
		if ( false === $result ) {
			throw new \RuntimeException( 'Unable to remove Campaign sections.' );
		}
	}

	private function hydrate( array $row ): CampaignSection {
		return new CampaignSection(
			(int) $row['id'],
			(int) $row['campaign_id'],
			(string) $row['title'],
			(string) $row['description'],
			(int) $row['image_id'],
			(string) $row['layout'],
			(string) $row['status'],
			(int) $row['display_order'],
			(string) ( $row['title_color'] ?? '' ),
			(string) ( $row['cta_bg_color'] ?? '' ),
			(string) ( $row['cta_text_color'] ?? '' ),
			(string) ( $row['copy_color'] ?? '' )
		);
	}
}