<?php

namespace WooCampaign\CampaignProduct;

use WooCampaign\Campaign\CampaignRepository;
use WooCampaign\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Service {
	public function __construct(
		private Repository $repository,
		private ProductAdapter $products,
		private CampaignRepository $campaigns,
	) {}

	public function replace( int $campaignId, array $inputRows, bool $notify = true ): array {
		if ( ! $this->campaigns->find( $campaignId ) ) {
			throw new \InvalidArgumentException( 'Campaign does not exist.' );
		}

		$rows = [];
		$seen = [];
		foreach ( $inputRows as $position => $input ) {
			$saleableId = absint( $input['saleable_id'] ?? 0 );
			$normalized = $this->products->normalizeSaleable( $saleableId );
			$price = wc_format_decimal( $input['campaign_price'] ?? '' );
			$status = in_array( (string) ( $input['status'] ?? 'active' ), [ 'active', 'paused' ], true ) ? (string) $input['status'] : 'active';
			if ( ! $normalized || '' === $price || (float) $price <= 0 ) {
				throw new \InvalidArgumentException( 'Campaign products require a valid saleable product and positive Campaign Price.' );
			}
			$key = $normalized['product_id'] . ':' . $normalized['variation_id'];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$rows[] = [
				'section_id'     => absint( $input['section_id'] ?? 0 ),
				'product_id'     => $normalized['product_id'],
				'variation_id'   => $normalized['variation_id'],
				'campaign_price' => $price,
				'campaign_copy'  => wp_kses_post( (string) ( $input['campaign_copy'] ?? '' ) ),
				'status'         => $status,
				'display_order'  => isset( $input['display_order'] ) ? absint( $input['display_order'] ) : (int) $position,
			];
		}

		$this->repository->replaceForCampaign( $campaignId, $rows );
		clean_post_cache( $campaignId );
		if ( $notify ) {
			do_action( 'woo_campaign_updated', $campaignId );
		}
		return $rows;
	}

	public function deleteCampaignProducts( int $campaignId ): void {
		$this->repository->deleteForCampaign( $campaignId );
	}
}
