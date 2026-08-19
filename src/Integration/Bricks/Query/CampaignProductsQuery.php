<?php

namespace NowCampaignStorefronts\Integration\Bricks\Query;

use NowCampaignStorefronts\Campaign\CampaignContext;
use NowCampaignStorefronts\CampaignProduct\CampaignProduct;
use NowCampaignStorefronts\CampaignProduct\Repository as CampaignProductRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks Query Loop type: "Campaign Products".
 *
 * Returns the current Campaign's active CampaignProduct domain objects
 * (never WC_Product) so every loop item keeps campaign_id / section_id /
 * product_id / variation_id for Dynamic Data and future commerce controls.
 */
final class CampaignProductsQuery {
	public const TYPE = 'campaign_products';

	public function __construct(
		private CampaignProductRepository $repository,
	) {}

	public function register(): void {
		add_filter( 'bricks/setup/control_options', [ $this, 'addQueryType' ] );
		add_filter( 'bricks/query/run', [ $this, 'run' ], 10, 2 );
		add_filter( 'bricks/query/loop_object_id', [ $this, 'loopObjectId' ], 10, 3 );
	}

	public function addQueryType( array $options ): array {
		$options['queryTypes'][ self::TYPE ] = esc_html__( 'Campaign Products', 'now-campaign-storefronts' );
		return $options;
	}

	/**
	 * @param mixed $results
	 * @param mixed $query Bricks\Query instance.
	 */
	public function run( $results, $query ) {
		if ( ! is_object( $query ) || ( $query->object_type ?? '' ) !== self::TYPE ) {
			return $results;
		}
		$campaignId = CampaignContext::resolveId( $this->currentPostId() );
		if ( $campaignId <= 0 ) {
			return [];
		}
		return $this->repository->forCampaign( $campaignId, true );
	}

	/**
	 * Stable loop id for the Bricks Dynamic Data lifecycle.
	 *
	 * Only rewrites object IDs for our own query type so other Bricks
	 * queries are never affected.
	 *
	 * @param mixed $objectId
	 * @param mixed $object
	 * @param mixed $queryId
	 */
	public function loopObjectId( $objectId, $object, $queryId ) {
		if ( $object instanceof CampaignProduct
			&& class_exists( '\Bricks\Query' )
			&& self::TYPE === \Bricks\Query::get_query_object_type( (string) $queryId )
		) {
			return $object->id;
		}
		return $objectId;
	}

	private function currentPostId(): int {
		// Same resolution as Bricks core: builder preview post id wins,
		// otherwise the queried post.
		if ( class_exists( '\Bricks\Database' ) && isset( \Bricks\Database::$page_data['preview_or_post_id'] ) ) {
			return (int) \Bricks\Database::$page_data['preview_or_post_id'];
		}
		return (int) get_the_ID();
	}
}
