<?php

namespace WooCampaign\Integration\Bricks\DynamicData;

use WooCampaign\Campaign\CampaignContext;
use WooCampaign\CampaignProduct\CampaignProduct;
use WooCampaign\CampaignProduct\CampaignProductPresentationResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks 2.3.10 Dynamic Data provider for NOW Campaign Storefronts tags.
 *
 * Registered through Bricks' official provider seam
 * (bricks/dynamic_data/register_providers) under the class name Bricks
 * expects (Provider_Woo_Campaign, via class_alias in CampaignDynamicData).
 * Bricks drives tag resolution through get_tag_value(), so Text, Heading,
 * Rich Text and Button content (including mixed strings such as
 * "Opening: {wc_campaign_title}") renders through the same native pipeline
 * as Bricks' own tags.
 *
 * Campaign Product tags read the current Bricks Query Loop object (a
 * CampaignProduct domain object) and resolve values through the shared
 * PresentationResolver, so Bricks output matches the native storefront.
 * Image tags return an attachment ID in image context (Bricks' own
 * featured_image contract) and a URL string in text/link context.
 */
final class ProviderWooCampaign extends \Bricks\Integrations\Dynamic_Data\Providers\Base {
	private const GROUP_CAMPAIGN = 'NOW Campaign Storefronts';
	private const GROUP_PRODUCT = 'NOW Campaign Storefronts Product';

	private const CAMPAIGN_TAGS = [
		'wc_campaign_id'             => 'ID',
		'wc_campaign_title'          => 'Title',
		'wc_campaign_slug'           => 'Slug',
		'wc_campaign_excerpt'        => 'Excerpt',
		'wc_campaign_featured_image' => 'Featured Image',
	];

	private const PRODUCT_TAGS = [
		'wc_campaign_product_id'       => 'ID',
		'wc_campaign_product_name'     => 'Name',
		'wc_campaign_product_variation' => 'Variation',
		'wc_campaign_product_image'    => 'Image',
		'wc_campaign_ref_price'        => 'Woo Reference Price',
		'wc_campaign_price'            => 'Campaign Price',
		'wc_campaign_savings'          => 'Savings',
		'wc_campaign_copy'             => 'Copy',
		'wc_campaign_stock_note'       => 'Stock Note',
		'wc_campaign_woo_product_id'   => 'Product ID',
		'wc_campaign_woo_variation_id' => 'Variation ID',
	];

	private static ?CampaignProductPresentationResolver $presentation = null;

	/**
	 * Inject the shared presentation resolver. Called once at plugin boot,
	 * before Bricks instantiates providers on init.
	 */
	public static function setPresentation( CampaignProductPresentationResolver $presentation ): void {
		self::$presentation = $presentation;
	}

	public static function load_me() {
		return true;
	}

	/**
	 * Populate $this->tags in the Bricks provider format. Called lazily by
	 * Base::get_tags() when Bricks merges all provider tags.
	 */
	public function register_tags() {
		foreach ( $this->get_tags_config() as $key => $tag ) {
			$this->tags[ $key ] = [
				'name'     => '{' . $key . '}',
				'label'    => $tag['label'],
				'group'    => $tag['group'],
				'provider' => $this->name,
			];
		}
	}

	public function get_tags_config() {
		$tags = [];
		foreach ( self::CAMPAIGN_TAGS as $name => $label ) {
			$tags[ $name ] = [
				'label' => $label,
				'group' => self::GROUP_CAMPAIGN,
			];
		}
		foreach ( self::PRODUCT_TAGS as $name => $label ) {
			$tags[ $name ] = [
				'label' => $label,
				'group' => self::GROUP_PRODUCT,
			];
		}
		return $tags;
	}

	/**
	 * Bricks 2.3.10 provider contract: ($tag, $post, $args, $context).
	 * Tag names arrive bare; $args are tag arguments (unused, tags stay
	 * argument-free), $context is text/image/link.
	 *
	 * @param mixed $tag
	 * @param mixed $post
	 * @param mixed $args
	 * @param mixed $context
	 * @return mixed
	 */
	public function get_tag_value( $tag, $post, $args = [], $context = 'text' ) {
		$tag     = (string) $tag;
		$context = (string) $context;

		if ( isset( self::PRODUCT_TAGS[ $tag ] ) ) {
			$product = $this->loopProduct();
			if ( null === $product || null === self::$presentation ) {
				return '';
			}
			$data = self::$presentation->resolve( $product );
			if ( null === $data ) {
				return '';
			}
			return $this->renderProductTag( $tag, $data, $context );
		}

		if ( isset( self::CAMPAIGN_TAGS[ $tag ] ) ) {
			$campaignId = CampaignContext::resolveId( $post instanceof \WP_Post ? (int) $post->ID : 0 );
			if ( $campaignId <= 0 ) {
				return '';
			}
			return $this->renderCampaignTag( $tag, $campaignId, $context );
		}

		return '';
	}

	/**
	 * @param array $data Normalized PresentationResolver contract.
	 * @return mixed
	 */
	private function renderProductTag( string $tag, array $data, string $context ) {
		switch ( $tag ) {
			case 'wc_campaign_product_id':
				return (string) $data['campaign_product_id'];
			case 'wc_campaign_product_name':
				return $data['display_title'];
			case 'wc_campaign_product_variation':
				return $data['variation'];
			case 'wc_campaign_product_image':
				return $this->imageValue( (int) $data['image_id'], $context );
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
	private function renderCampaignTag( string $tag, int $campaignId, string $context ) {
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
				return $this->imageValue( (int) get_post_thumbnail_id( $campaignId ), $context );
			default:
				return '';
		}
	}

	/**
	 * Image contract mirrors Bricks' own featured_image tag: an attachment
	 * ID for the image element (context 'image'), a URL string otherwise so
	 * text/link contexts get something displayable.
	 */
	private function imageValue( int $imageId, string $context ) {
		if ( $imageId <= 0 || ! wp_attachment_is_image( $imageId ) ) {
			return '';
		}
		if ( 'image' === $context ) {
			return $imageId;
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
