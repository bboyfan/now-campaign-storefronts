<?php

namespace Bboyfan\NowCampaignStorefronts\Pricing;

use Bboyfan\NowCampaignStorefronts\Campaign\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignBulkPricing {
	public function config( int $campaignId ): array {
		$stored = get_post_meta( $campaignId, Meta::BULK_PRICING, true );
		return Meta::sanitizeBulkPricing( is_array( $stored ) ? $stored : [] );
	}

	public function tiers( int $campaignId ): array {
		$config = $this->config( $campaignId );
		return $config['enabled'] ? $config['tiers'] : [];
	}

	public function discountPercentForQuantity( int $campaignId, int $quantity ): float {
		if ( $quantity < 2 ) {
			return 0.0;
		}

		$discount = 0.0;
		foreach ( $this->tiers( $campaignId ) as $tier ) {
			$minQty = absint( $tier['min_qty'] ?? 0 );
			if ( $minQty <= 0 || $quantity < $minQty ) {
				break;
			}
			$discount = (float) ( $tier['discount_percent'] ?? 0 );
		}
		return max( 0.0, min( 99.9999, $discount ) );
	}

	public function effectivePrice( int $campaignId, float $campaignPrice, int $quantity ): float {
		$discount = $this->discountPercentForQuantity( $campaignId, $quantity );
		if ( $discount <= 0 ) {
			return (float) wc_format_decimal( $campaignPrice, wc_get_price_decimals() );
		}
		$price = $campaignPrice * ( 1 - ( $discount / 100 ) );
		return (float) wc_format_decimal( $price, wc_get_price_decimals() );
	}
}
