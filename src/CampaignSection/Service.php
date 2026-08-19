<?php

namespace NowCampaignStorefronts\CampaignSection;

use NowCampaignStorefronts\Campaign\CampaignRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Service {
	public function __construct(
		private Repository $repository,
		private CampaignRepository $campaigns,
	) {}

	public function save( int $campaignId, array $inputRows, bool $notify = true ): array {
		if ( ! $this->campaigns->find( $campaignId ) ) {
			throw new \InvalidArgumentException( 'Campaign does not exist.' );
		}

		$rows = [];
		foreach ( $inputRows as $position => $input ) {
			$row = [
				'id'            => absint( $input['id'] ?? 0 ),
				'client_key'    => sanitize_key( (string) ( $input['client_key'] ?? 'section-' . $position ) ),
				'title'         => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
				'description'   => wp_kses_post( (string) ( $input['description'] ?? '' ) ),
				'image_id'      => absint( $input['image_id'] ?? 0 ),
				'layout'        => in_array( (string) ( $input['layout'] ?? '' ), CampaignSection::layouts(), true ) ? (string) $input['layout'] : CampaignSection::LAYOUT_QUICK_ORDER,
				'status'        => 'paused' === (string) ( $input['status'] ?? 'active' ) ? 'paused' : 'active',
				'display_order' => isset( $input['display_order'] ) ? absint( $input['display_order'] ) : $position,
			];
			foreach ( [ 'title_color', 'copy_color', 'cta_bg_color', 'cta_text_color' ] as $colorKey ) {
				if ( array_key_exists( $colorKey, $input ) ) {
					$row[ $colorKey ] = sanitize_hex_color( (string) $input[ $colorKey ] ) ?: '';
				}
			}
			$rows[] = $row;
		}

		if ( ! $rows ) {
			$rows[] = [
				'id'            => 0,
				'client_key'    => 'section-default',
				'title'         => '',
				'description'   => '',
				'image_id'      => 0,
				'layout'        => CampaignSection::LAYOUT_QUICK_ORDER,
				'status'        => 'active',
				'display_order' => 0,
			];
		}

		$keyMap = $this->repository->saveForCampaign( $campaignId, $rows );
		clean_post_cache( $campaignId );
		if ( $notify ) {
			do_action( 'nowcastf_sections_updated', $campaignId );
		}
		return $keyMap;
	}

	public function ensureDefault( int $campaignId ): int {
		$sections = $this->repository->forCampaign( $campaignId );
		if ( $sections ) {
			return $sections[0]->id;
		}
		return $this->repository->createDefault( $campaignId );
	}

	public function deleteCampaignSections( int $campaignId ): void {
		$this->repository->deleteForCampaign( $campaignId );
	}
}