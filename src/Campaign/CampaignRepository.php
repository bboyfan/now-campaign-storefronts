<?php

namespace Bboyfan\NowCampaignStorefronts\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignRepository {
	public function find( int $campaignId ): ?\WP_Post {
		$post = get_post( $campaignId );
		if ( ! $post instanceof \WP_Post || PostType::TYPE !== $post->post_type ) {
			return null;
		}
		return $post;
	}

	public function isActive( int $campaignId, ?int $now = null ): bool {
		$post = $this->find( $campaignId );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( (bool) get_post_meta( $campaignId, Meta::ARCHIVED, true ) ) {
			return false;
		}

		$now = $now ?? time();
		$start = (int) get_post_meta( $campaignId, Meta::START_AT, true );
		$end = (int) get_post_meta( $campaignId, Meta::END_AT, true );

		if ( $start > 0 && $now < $start ) {
			return false;
		}
		if ( $end > 0 && $now > $end ) {
			return false;
		}
		return true;
	}

	public function title( int $campaignId ): string {
		$post = $this->find( $campaignId );
		return $post ? $post->post_title : '';
	}

	public function slug( int $campaignId ): string {
		$post = $this->find( $campaignId );
		return $post ? $post->post_name : '';
	}
}
