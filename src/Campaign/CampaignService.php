<?php

namespace WooCampaign\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignService {
	public function __construct( private CampaignRepository $campaigns ) {}

	public function isAvailable( int $campaignId ): bool {
		return $this->campaigns->isActive( $campaignId );
	}

	public function statusLabel( int $campaignId ): string {
		$post = $this->campaigns->find( $campaignId );
		if ( ! $post ) {
			return 'missing';
		}
		if ( (bool) get_post_meta( $campaignId, Meta::ARCHIVED, true ) ) {
			return 'archived';
		}
		if ( 'auto-draft' === $post->post_status ) {
			return 'draft';
		}
		if ( 'publish' !== $post->post_status ) {
			return $post->post_status;
		}
		$now = time();
		$start = (int) get_post_meta( $campaignId, Meta::START_AT, true );
		$end = (int) get_post_meta( $campaignId, Meta::END_AT, true );
		if ( $start > 0 && $now < $start ) {
			return 'scheduled';
		}
		if ( $end > 0 && $now > $end ) {
			return 'expired';
		}
		return 'active';
	}
}
