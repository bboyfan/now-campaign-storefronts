<?php

namespace WooCampaign\Integration\Bricks\Conditions;

use WooCampaign\Campaign\CampaignContext;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks element conditions: "WC Campaign / Current Campaign".
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
		add_filter( 'bricks/conditions/result', [ $this, 'evaluate' ], 10, 3 );
	}

	public function registerGroups( array $groups ): array {
		$groups[] = [
			'name'  => self::GROUP,
			'label' => __( 'WC Campaign', 'wc-campaign' ),
		];
		return $groups;
	}

	public function registerOptions( array $options ): array {
		$options[] = [
			'key'     => self::KEY_CURRENT,
			'group'   => self::GROUP,
			'label'   => __( 'Current Campaign', 'wc-campaign' ),
			'compare' => [
				'type'        => 'select',
				'options'     => [
					'==' => __( 'is', 'wc-campaign' ),
					'!=' => __( 'is not', 'wc-campaign' ),
				],
				'placeholder' => __( 'is', 'wc-campaign' ),
			],
			'value'   => [
				'type'        => 'select',
				'options'     => $this->campaignOptions(),
				'multiple'    => true,
				'placeholder' => __( 'Select campaigns', 'wc-campaign' ),
			],
		];
		return $options;
	}

	/**
	 * @param mixed $condition The full condition array (key/compare/value).
	 */
	public function evaluate( bool $renderSet, string $key, array $condition ): bool {
		if ( self::KEY_CURRENT !== $key || ( $condition['group'] ?? '' ) !== self::GROUP ) {
			return $renderSet;
		}
		$campaignId = CampaignContext::currentId();
		if ( $campaignId <= 0 ) {
			return false;
		}
		$selected = array_map( 'absint', (array) ( $condition['value'] ?? [] ) );
		$matches = in_array( $campaignId, $selected, true );
		return ( $condition['compare'] ?? '==' ) === '!=' ? ! $matches : $matches;
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
