<?php

namespace NowCampaignStorefronts\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the current Campaign identity from WordPress domain data only.
 *
 * Bricks-specific resolution (builder preview, builder AJAX, loop objects)
 * lives in the Bricks integration adapter; it feeds a candidate ID into
 * resolveId() and lets this class validate it.
 */
final class CampaignContext {
	/**
	 * Resolve a campaign ID. Priority: validated explicit candidate, then the
	 * singular queried object. Returns 0 when there is no Campaign context.
	 */
	public static function resolveId( int $candidate = 0 ): int {
		$candidate = absint( $candidate );
		if ( $candidate > 0 && self::isCampaignPost( get_post( $candidate ) ) ) {
			return $candidate;
		}
		$queried = get_queried_object();
		if ( $queried instanceof \WP_Post && PostType::TYPE === $queried->post_type ) {
			return (int) $queried->ID;
		}
		return 0;
	}

	/** Resolve the campaign ID of the current request, if any. */
	public static function currentId(): int {
		return self::resolveId( (int) get_the_ID() );
	}

	/** Whether the given campaign ID points to an existing Campaign post. */
	public static function exists( int $campaignId ): bool {
		return $campaignId > 0 && ( new CampaignRepository() )->find( $campaignId ) !== null;
	}

	private static function isCampaignPost( ?\WP_Post $post ): bool {
		return $post instanceof \WP_Post && PostType::TYPE === $post->post_type;
	}
}
