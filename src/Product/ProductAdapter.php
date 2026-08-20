<?php

namespace Bboyfan\NowCampaignStorefronts\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProductAdapter {
	public function get( int $id ): ?\WC_Product {
		$product = wc_get_product( $id );
		return $product instanceof \WC_Product ? $product : null;
	}

	public function normalizeSaleable( int $saleableId ): ?array {
		$product = $this->get( $saleableId );
		if ( ! $product ) {
			return null;
		}
		if ( $product->is_type( 'variation' ) ) {
			return [
				'product_id'   => (int) $product->get_parent_id(),
				'variation_id' => (int) $product->get_id(),
				'product'      => $product,
			];
		}
		if ( $product->is_type( 'simple' ) ) {
			return [
				'product_id'   => (int) $product->get_id(),
				'variation_id' => 0,
				'product'      => $product,
			];
		}
		return null;
	}

	public function basePrice( \WC_Product $product ): string {
		return wc_format_decimal( $product->get_price( 'edit' ) );
	}

	public function isPurchasable( \WC_Product $product ): bool {
		return $product->is_purchasable() && $product->is_in_stock();
	}
}
