<?php

namespace WooCampaign\Integration\Bricks\DynamicData;

use WooCampaign\Campaign\CampaignContext;
use WooCampaign\CampaignProduct\CampaignProduct;
use WooCampaign\CampaignProduct\CampaignProductPresentationResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks dynamic data tags for Campaign and Campaign Product values.
 *
 * Tag names are registered bare (Bricks strips braces before filtering).
 * Campaign Product tags read the current Bricks Query Loop object (a
 * CampaignProduct domain object) and resolve values through the shared
 * PresentationResolver, so Bricks output always matches the native
 * storefront. Outside a loop they safely return an empty value. Image tags
 * return an attachment URL string, the same contract Bricks' own
 * featured_image tag uses.
 */
final class CampaignDynamicData {
	private const GROUP_CAMPAIGN = 'WC Campaign';
	private const GROUP_PRODUCT = 'WC Campaign Product';

	private const CAMPAIGN_TAGS = [
		'wc_campaign_id'            => 'ID',
		'wc_campaign_title'         => 'Title',
		'wc_campaign_slug'          => 'Slug',
		'wc_campaign_excerpt'       => 'Excerpt',
		'wc_campaign_featured_image' => 'Featured Image',
	];

	private const PRODUCT_TAGS = [
		'wc_campaign_product_id'          => 'ID',
		'wc_campaign_product_name'        => 'Name',
		'wc_campaign_product_variation'   => 'Variation',
		'wc_campaign_product_image'       => 'Image',
		'wc_campaign_ref_price'           => 'Woo Reference Price',
		'wc_campaign_price'               => 'Campaign Price',
		'wc_campaign_savings'             => 'Savings',
		'wc_campaign_copy'                => 'Copy',
		'wc_campaign_stock_note'          => 'Stock Note',
		'wc_campaign_woo_product_id'      => 'Product ID',
		'wc_campaign_woo_variation_id'    => 'Variation ID',
	];

	public function __construct(
		private CampaignProductPresentationResolver $presentation,
	) {}

	public function register(): void {
		add_filter( 'bricks/dynamic_tags_list', [ $this, 'registerTags' ] );
		add_filter( 'bricks/dynamic_data/render_tag', [ $this, 'renderTag' ], 10, 3 );
	}

	/**
	 * Bricks 2.x tags list is a flat array of ['name', 'label', 'group'].
	 */
	public function registerTags( array $tags ): array {
		foreach ( self::CAMPAIGN_TAGS as $name => $label ) {
			$tags[] = [ 'name' => $name, 'label' => $label, 'group' => self::GROUP_CAMPAIGN ];
		}
		foreach ( self::PRODUCT_TAGS as $name => $label ) {
			$tags[] = [ 'name' => $name, 'label' => $label, 'group' => self::GROUP_PRODUCT ];
		}
		return $tags;
	}

	/**
	 * @param mixed $value Value so far in the filter chain: the bare tag name
	 *                     for unknown tags, Bricks' resolved value otherwise.
	 * @param mixed $post
	 * @param mixed $context
	 * @return mixed
	 */
	public function renderTag( $value, $post, $context ) {
		$tag = is_string( $value ) ? trim( $value, '{}' ) : '';
		if ( ! isset( self::CAMPAIGN_TAGS[ $tag ] ) && ! isset( self::PRODUCT_TAGS[ $tag ] ) ) {
			return $value;
		}

		if ( isset( self::PRODUCT_TAGS[ $tag ] ) ) {
			$product = $this->loopProduct();
			if ( null === $product ) {
				return '';
			}
			$data = $this->presentation->resolve( $product );
			if ( null === $data ) {
				return '';
			}
			return $this->renderProductTag( $tag, $data );
		}

		$campaignId = CampaignContext::resolveId( $post instanceof \WP_Post ? (int) $post->ID : 0 );
		if ( $campaignId <= 0 ) {
			return '';
		}
		return $this->renderCampaignTag( $tag, $campaignId );
	}

	/**
	 * @param array $data Normalized PresentationResolver contract.
	 * @return mixed
	 */
	private function renderProductTag( string $tag, array $data ) {
		switch ( $tag ) {
			case 'wc_campaign_product_id':
				return (string) $data['campaign_product_id'];
			case 'wc_campaign_product_name':
				return $data['display_title'];
			case 'wc_campaign_product_variation':
				return $data['variation'];
			case 'wc_campaign_product_image':
				return $this->imageUrl( (int) $data['image_id'] );
			case 'wc_campaign_ref_price':
				return $data['base'] > 0 ? wc_price( $data['base'] ) : '';
			case 'wc_campaign_price':
				return wc_price( $data['campaign'] );
			case 'wc_campaign_savings':
				return ( $data['base'] > 0 && $data['campaign'] < $data['base'] ) ? wc_price( $data['base'] - $data['campaign'] ) : '';
			case 'wc_campaign_copy':
				return $data['copy'];
			case 'wc_campaign_stock_note':
				return $data['stock_note'];
			case 'wc_campaign_woo_product_id':
				return (string) $data['product_id'];
			case 'wc_campaign_woo_variation_id':
				return (string) $data['variation_id'];
			default:
				return '';
		}
	}

	/** @return mixed */
	private function renderCampaignTag( string $tag, int $campaignId ) {
		switch ( $tag ) {
			case 'wc_campaign_id':
				return (string) $campaignId;
			case 'wc_campaign_title':
				return get_the_title( $campaignId );
			case 'wc_campaign_slug':
				$post = get_post( $campaignId );
				return $post ? (string) $post->post_name : '';
			case 'wc_campaign_excerpt':
				return get_the_excerpt( $campaignId );
			case 'wc_campaign_featured_image':
				return $this->imageUrl( (int) get_post_thumbnail_id( $campaignId ) );
			default:
				return '';
		}
	}

	/** Image dynamic data returns an attachment URL string (Bricks contract). */
	private function imageUrl( int $imageId ): string {
		if ( $imageId <= 0 || ! wp_attachment_is_image( $imageId ) ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $imageId, 'full' );
		return is_string( $url ) ? $url : '';
	}

	private function loopProduct(): ?CampaignProduct {
		if ( ! class_exists( '\Bricks\Query' ) || ! method_exists( '\Bricks\Query', 'get_loop_object' ) ) {
			return null;
		}
		$loopObject = \Bricks\Query::get_loop_object();
		return $loopObject instanceof CampaignProduct ? $loopObject : null;
	}
}
