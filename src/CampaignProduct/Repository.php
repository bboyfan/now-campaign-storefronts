<?php

namespace WooCampaign\CampaignProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Repository {
	public function find( int $id ): ?CampaignProduct {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . Table::name() . ' WHERE id = %d LIMIT 1', $id ),
			ARRAY_A
		);
		return is_array( $row ) ? $this->hydrate( $row ) : null;
	}

	public function forCampaign( int $campaignId, bool $activeOnly = false ): array {
		global $wpdb;
		$sql = 'SELECT * FROM ' . Table::name() . ' WHERE campaign_id = %d';
		$args = [ $campaignId ];
		if ( $activeOnly ) {
			$sql .= ' AND status = %s';
			$args[] = 'active';
		}
		$sql .= ' ORDER BY display_order ASC, id ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
		return array_map( fn( array $row ): CampaignProduct => $this->hydrate( $row ), $rows ?: [] );
	}

	public function forSection( int $campaignId, int $sectionId, bool $activeOnly = false ): array {
		global $wpdb;
		$sql = 'SELECT * FROM ' . Table::name() . ' WHERE campaign_id = %d AND section_id = %d';
		$args = [ $campaignId, $sectionId ];
		if ( $activeOnly ) {
			$sql .= ' AND status = %s';
			$args[] = 'active';
		}
		$sql .= ' ORDER BY display_order ASC, id ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
		return array_map( fn( array $row ): CampaignProduct => $this->hydrate( $row ), $rows ?: [] );
	}

	public function replaceForCampaign( int $campaignId, array $rows ): void {
		global $wpdb;
		$table = Table::name();
		$existing = $this->forCampaign( $campaignId );
		$existingBySaleable = [];
		foreach ( $existing as $item ) {
			$existingBySaleable[ $item->productId . ':' . $item->variationId ] = $item;
		}
		$keptIds = [];

		$now = current_time( 'mysql', true );
		foreach ( $rows as $row ) {
			$productId = (int) $row['product_id'];
			$variationId = (int) $row['variation_id'];
			$data = [
				'section_id'     => (int) ( $row['section_id'] ?? 0 ),
				'campaign_price' => (string) $row['campaign_price'],
				'campaign_copy'  => (string) ( $row['campaign_copy'] ?? '' ),
				'status'         => (string) $row['status'],
				'display_order'  => (int) $row['display_order'],
				'updated_at'     => $now,
			];
			$key = $productId . ':' . $variationId;
			$existingItem = $existingBySaleable[ $key ] ?? null;

			if ( $existingItem ) {
				$result = $wpdb->update(
					$table,
					$data,
					[ 'id' => $existingItem->id, 'campaign_id' => $campaignId ],
					[ '%d', '%s', '%s', '%s', '%d', '%s' ],
					[ '%d', '%d' ]
				);
				if ( false === $result ) {
					throw new \RuntimeException( 'Could not update campaign product: ' . $wpdb->last_error );
				}
				$keptIds[ $existingItem->id ] = true;
				continue;
			}

			$data['campaign_id'] = $campaignId;
			$data['product_id'] = $productId;
			$data['variation_id'] = $variationId;
			$data['created_at'] = $now;
			$result = $wpdb->insert(
				$table,
				$data,
				[ '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s' ]
			);
			if ( false === $result || (int) $wpdb->insert_id <= 0 ) {
				throw new \RuntimeException( 'Could not create campaign product: ' . $wpdb->last_error );
			}
			$keptIds[ (int) $wpdb->insert_id ] = true;
		}

		foreach ( $existing as $item ) {
			if ( isset( $keptIds[ $item->id ] ) ) {
				continue;
			}
			$result = $wpdb->delete( $table, [ 'id' => $item->id, 'campaign_id' => $campaignId ], [ '%d', '%d' ] );
			if ( false === $result ) {
				throw new \RuntimeException( 'Could not remove campaign product: ' . $wpdb->last_error );
			}
		}
	}

	public function deleteForCampaign( int $campaignId ): void {
		global $wpdb;
		$result = $wpdb->delete( Table::name(), [ 'campaign_id' => $campaignId ], [ '%d' ] );
		if ( false === $result ) {
			throw new \RuntimeException( 'Could not remove campaign products: ' . $wpdb->last_error );
		}
	}

	private function hydrate( array $row ): CampaignProduct {
		return new CampaignProduct(
			(int) $row['id'],
			(int) $row['campaign_id'],
			(int) ( $row['section_id'] ?? 0 ),
			(int) $row['product_id'],
			(int) $row['variation_id'],
			(string) $row['campaign_price'],
			(string) ( $row['campaign_copy'] ?? '' ),
			(string) $row['status'],
			(int) $row['display_order']
		);
	}
}
