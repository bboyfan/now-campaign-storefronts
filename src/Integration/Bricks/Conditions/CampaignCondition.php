<?php

namespace WooCampaign\Integration\Bricks\Conditions;

use WooCampaign\Campaign\CampaignContext;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks element conditions: "NOW Campaign Storefronts / Current Campaign".
 *
 * Multi-select Campaign list with == / != compare operators evaluated with
 * overlap semantics against the current Campaign context. Display-only, not
 * a security boundary. Outside a Campaign page every condition evaluates to
 * false (safe, no exception).
 */
final class CampaignCondition {
	private const GROUP = 'woo_campaign';
	private const KEY_CURRENT = 'campaign_current';

	public function register(): void {
		add_filter( 'bricks/conditions/groups', [ $this, 'registerGroups' ] );
		add_filter( 'bricks/conditions/options', [ $this, 'registerOptions' ] );
		// Late priority: NOW Campaign Storefronts is the final authority for its own key,
		// so no generic extension fallback can overwrite the result after us.
		add_filter( 'bricks/conditions/result', [ $this, 'evaluate' ], 9999, 3 );
	}

	public function registerGroups( array $groups ): array {
		$groups[] = [
			'name'  => self::GROUP,
			'label' => __( 'NOW Campaign Storefronts', 'now-campaign-storefronts' ),
		];
		return $groups;
	}

	public function registerOptions( array $options ): array {
		$options[] = [
			'key'     => self::KEY_CURRENT,
			'group'   => self::GROUP,
			'label'   => __( 'Current Campaign', 'now-campaign-storefronts' ),
			'compare' => [
				'type'        => 'select',
				'options'     => [
					'==' => __( 'is', 'now-campaign-storefronts' ),
					'!=' => __( 'is not', 'now-campaign-storefronts' ),
				],
				'placeholder' => __( 'is', 'now-campaign-storefronts' ),
			],
			'value'   => [
				'type'        => 'select',
				'options'     => $this->campaignOptions(),
				'multiple'    => true,
				'placeholder' => __( 'Select campaigns', 'now-campaign-storefronts' ),
			],
		];
		return $options;
	}

	/**
	 * @param mixed $condition The full condition array (key/compare/value).
	 */
	public function evaluate( bool $renderSet, string $key, array $condition ): bool {
		if ( self::KEY_CURRENT !== $key ) {
			return $renderSet;
		}

		// No Campaign context (any page outside a Campaign): the condition
		// never matches, regardless of compare.
		$campaignId = $this->currentCampaignId();
		if ( $campaignId <= 0 ) {
			return false;
		}

		// Normalize string/int IDs, arrays of either; drop zeros/duplicates.
		$selected = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', (array) ( $condition['value'] ?? [] ) )
				)
			)
		);
		$matches = in_array( $campaignId, $selected, true );

		return ( $condition['compare'] ?? '==' ) === '!=' ? ! $matches : $matches;
	}

	/**
	 * Resolve the current Campaign from Bricks page context first (builder
	 * preview / AJAX render), falling back to the WordPress queried object.
	 */
	private function currentCampaignId(): int {
		if ( class_exists( '\Bricks\Database' ) ) {
			$previewId = (int) ( \Bricks\Database::$page_data['preview_or_post_id'] ?? 0 );
			if ( $previewId > 0 ) {
				return CampaignContext::resolveId( $previewId );
			}
		}
		return CampaignContext::currentId();
	}

	private function campaignOptions(): array {
		$options = [];
		$campaigns = get_posts(
			[
				'post_type'      => PostType::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
		foreach ( $campaigns as $campaign ) {
			$options[ (int) $campaign->ID ] = $campaign->post_title;
		}
		return $options;
	}
}
