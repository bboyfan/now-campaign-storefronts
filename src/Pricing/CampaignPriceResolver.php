<?php

namespace WooCampaign\Pricing;

use WooCampaign\Campaign\CampaignRepository;
use WooCampaign\CampaignProduct\CampaignProduct;
use WooCampaign\CampaignProduct\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignPriceResolver {
	public function __construct(
		private CampaignRepository $campaigns,
		private Repository $campaignProducts,
	) {}

	public function resolve( int $campaignId, int $campaignProductId, int $productId, int $variationId ): ?CampaignProduct {
		if ( ! $this->campaigns->isActive( $campaignId ) ) {
			return null;
		}
		$item = $this->campaignProducts->find( $campaignProductId );
		if ( ! $item || ! $item->isActive() || $item->campaignId !== $campaignId ) {
			return null;
		}
		if ( $item->productId !== $productId || $item->variationId !== $variationId ) {
			return null;
		}
		return $item;
	}

	public function resolveFromCartItem( array $cartItem ): ?CampaignProduct {
		return $this->resolve(
			absint( $cartItem['_woo_campaign_id'] ?? 0 ),
			absint( $cartItem['_woo_campaign_product_id'] ?? 0 ),
			absint( $cartItem['product_id'] ?? 0 ),
			absint( $cartItem['variation_id'] ?? 0 )
		);
	}
}
