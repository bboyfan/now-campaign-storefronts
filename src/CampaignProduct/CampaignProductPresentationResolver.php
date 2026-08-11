<?php

namespace WooCampaign\CampaignProduct;

use WooCampaign\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for Campaign Product presentation data.
 *
 * Shared by the native storefront renderer and the Bricks dynamic data tags,
 * so both always produce identical values. Returns a normalized array (the
 * contract the native renderer already used); no DTO for now.
 */
final class CampaignProductPresentationResolver {
	public function __construct(
		private ProductAdapter $products,
	) {}

	/**
	 * @return array{
	 *     product: \WC_Product,
	 *     campaign_product_id: int,
	 *     campaign_id: int,
	 *     product_id: int,
	 *     variation_id: int,
	 *     title: string,
	 *     display_title: string,
	 *     variation: string,
	 *     copy: string,
	 *     image_id: int,
	 *     base: float,
	 *     campaign: float,
	 *     available: bool,
	 *     stock_note: string,
	 * }|null
	 */
	public function resolve( CampaignProduct $row ): ?array {
		$product = $this->products->get( $row->saleableId() );
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}
		$parent = $row->variationId > 0 ? $this->products->get( $row->productId ) : $product;
		$displayProduct = $parent instanceof \WC_Product ? $parent : $product;
		$imageId = $product->get_image_id() ?: $displayProduct->get_image_id();
		$available = $product->is_purchasable() && $product->is_in_stock();
		$title = $this->normalizeName( $displayProduct->get_name() );
		$variation = $product instanceof \WC_Product_Variation ? $this->variationLabel( $product ) : '';
		$displayTitle = '' !== $variation ? $title . ' - ' . $variation : $title;

		return [
			'product'             => $product,
			'campaign_product_id' => $row->id,
			'campaign_id'         => $row->campaignId,
			'product_id'          => $row->productId,
			'variation_id'        => $row->variationId,
			'title'               => $title,
			'display_title'       => $displayTitle,
			'variation'           => $variation,
			'copy'                => trim( wp_strip_all_tags( (string) $row->campaignCopy ) ),
			'image_id'            => (int) $imageId,
			'base'                => (float) $product->get_price( 'edit' ),
			'campaign'            => (float) $row->campaignPrice,
			'available'           => $available,
			'stock_note'          => $available ? $this->stockNote( $product ) : '',
		];
	}

	private function stockNote( \WC_Product $product ): string {
		if ( ! $product->managing_stock() ) {
			return '';
		}
		$quantity = $product->get_stock_quantity();
		if ( null === $quantity || $quantity <= 0 ) {
			return '';
		}
		$threshold = function_exists( 'wc_get_low_stock_amount' ) ? (int) wc_get_low_stock_amount( $product ) : 2;
		if ( $threshold > 0 && $quantity <= $threshold ) {
			return sprintf( /* translators: %d: remaining stock quantity */ __( 'Only %d left', 'wc-campaign' ), $quantity );
		}
		return '';
	}

	private function variationLabel( \WC_Product_Variation $variation ): string {
		$label = wc_get_formatted_variation( $variation, true, false, false );
		return $this->normalizeName( is_string( $label ) ? $label : '' );
	}

	private function normalizeName( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/\s+/u', ' ', $value );
		return trim( is_string( $value ) ? $value : '' );
	}
}
