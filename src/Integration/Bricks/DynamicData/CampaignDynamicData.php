<?php

namespace WooCampaign\Integration\Bricks\DynamicData;

use WooCampaign\CampaignProduct\CampaignProductPresentationResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks Dynamic Data registration for WC Campaign tags.
 *
 * Tag rendering now lives in ProviderWooCampaign, a real Bricks 2.3.10
 * provider registered through bricks/dynamic_data/register_providers (the
 * key is added in Plugin bootstrap, before the Bricks theme loads). That
 * pipeline parses tags inside Text/Heading/Rich Text/Button content —
 * including mixed strings — and resolves them via the provider's
 * get_tag_value(). Bricks discovers the provider class by the name
 * Bricks\Integrations\Dynamic_Data\Providers\Provider_Woo_Campaign, which
 * this class aliases to ProviderWooCampaign.
 */
final class CampaignDynamicData {
	private const BRICKS_PROVIDER_CLASS = 'Bricks\Integrations\Dynamic_Data\Providers\Provider_Woo_Campaign';

	public function __construct(
		private CampaignProductPresentationResolver $presentation,
	) {}

	public function register(): void {
		ProviderWooCampaign::setPresentation( $this->presentation );

		// Bricks instantiates providers on init (priority 10000); alias early
		// enough so class_exists() resolves when register_providers() runs.
		if ( ! class_exists( self::BRICKS_PROVIDER_CLASS, false ) ) {
			class_alias( ProviderWooCampaign::class, self::BRICKS_PROVIDER_CLASS );
		}
	}
}
