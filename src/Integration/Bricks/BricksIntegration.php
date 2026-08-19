<?php

namespace NowCampaignStorefronts\Integration\Bricks;

use NowCampaignStorefronts\Campaign\CampaignContext;
use NowCampaignStorefronts\Campaign\PostType;
use NowCampaignStorefronts\CampaignProduct\CampaignProductPresentationResolver;
use NowCampaignStorefronts\CampaignProduct\Repository as CampaignProductRepository;
use NowCampaignStorefronts\Integration\Bricks\Conditions\CampaignCondition;
use NowCampaignStorefronts\Integration\Bricks\DynamicData\CampaignDynamicData;
use NowCampaignStorefronts\Integration\Bricks\Query\CampaignProductsQuery;
use NowCampaignStorefronts\Storefront\CampaignRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks integration entry point.
 *
 * Registered only when the Bricks theme is active. Owns the official Bricks
 * hooks (template ownership capture, builder post type support) and delegates
 * Query / Dynamic Data / Conditions to their dedicated classes. Core
 * (Storefront, Campaign) never imports Bricks classes directly.
 */
final class BricksIntegration {
	private static bool $ownsCampaignPage = false;

	public function __construct(
		private CampaignProductRepository $campaignProducts,
		private CampaignProductPresentationResolver $presentation,
	) {}

	public function register(): void {
		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return;
		}
		add_filter( 'bricks/builder/supported_post_types', [ $this, 'supportCampaignPostType' ] );
		add_filter( 'bricks/active_templates', [ $this, 'captureOwnership' ], 10, 3 );
		add_filter( CampaignRenderer::FILTER_PRESENTATION_OWNER, [ $this, 'presentationOwner' ], 10, 2 );

		( new CampaignProductsQuery( $this->campaignProducts ) )->register();
		( new CampaignDynamicData( $this->presentation ) )->register();
		( new CampaignCondition() )->register();
	}

	public function supportCampaignPostType( array $postTypes ): array {
		if ( ! in_array( PostType::TYPE, $postTypes, true ) ) {
			$postTypes[] = PostType::TYPE;
		}
		return $postTypes;
	}

	/**
	 * Capture Bricks content template ownership for the current Campaign page
	 * via the official bricks/active_templates hook (Bricks applies it on the
	 * "wp" action, before any template_include decision).
	 *
	 * Only content ownership passes (content_type === 'content') update the
	 * flag; header/footer/archive template passes must not flip it.
	 *
	 * @param mixed $activeTemplates Bricks active template map (header/content/footer).
	 * @param mixed $postId
	 * @param mixed $contentType
	 */
	public function captureOwnership( $activeTemplates, $postId = 0, $contentType = '' ): mixed {
		if ( 'content' !== (string) $contentType ) {
			return $activeTemplates;
		}
		$campaignId = CampaignContext::resolveId( (int) $postId );
		if ( $campaignId > 0 && is_array( $activeTemplates ) ) {
			$contentTemplate = (int) ( $activeTemplates['content'] ?? 0 );
			// Exclude Bricks' "no template found" fallback, which assigns the
			// campaign post itself as the content template.
			self::$ownsCampaignPage = $contentTemplate > 0
				&& $contentTemplate !== $campaignId
				&& BRICKS_DB_TEMPLATE_SLUG === get_post_type( $contentTemplate );
		}
		return $activeTemplates;
	}

	/** Feed the storefront ownership seam: 'bricks' when a Bricks content template owns this page. */
	public function presentationOwner( string $owner, int $campaignId ): string {
		return self::$ownsCampaignPage ? 'bricks' : $owner;
	}
}
