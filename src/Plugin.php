<?php

namespace NowCampaignStorefronts;

use NowCampaignStorefronts\Admin\CampaignBulkPricing as CampaignBulkPricingAdmin;
use NowCampaignStorefronts\Admin\CampaignDuplicate as CampaignDuplicateAdmin;
use NowCampaignStorefronts\Admin\CampaignEditor;
use NowCampaignStorefronts\Admin\CampaignList;
use NowCampaignStorefronts\Admin\CampaignPresentation;
use NowCampaignStorefronts\Admin\CampaignReportAdmin;
use NowCampaignStorefronts\Campaign\CampaignDuplicator;
use NowCampaignStorefronts\Campaign\CampaignRepository;
use NowCampaignStorefronts\Campaign\CampaignService;
use NowCampaignStorefronts\Campaign\Meta;
use NowCampaignStorefronts\Campaign\PostType;
use NowCampaignStorefronts\CampaignProduct\CampaignProductPresentationResolver;
use NowCampaignStorefronts\CampaignProduct\Repository as CampaignProductRepository;
use NowCampaignStorefronts\CampaignProduct\Service as CampaignProductService;
use NowCampaignStorefronts\CampaignSection\Repository as CampaignSectionRepository;
use NowCampaignStorefronts\CampaignSection\Service as CampaignSectionService;
use NowCampaignStorefronts\Cart\AjaxController;
use NowCampaignStorefronts\Cart\CartService;
use NowCampaignStorefronts\Cart\CartValidator;
use NowCampaignStorefronts\Install\Migrator;
use NowCampaignStorefronts\Integration\Bricks\BricksIntegration;
use NowCampaignStorefronts\Order\OrderAttribution;
use NowCampaignStorefronts\Order\OrderCampaignIndex;
use NowCampaignStorefronts\Pricing\CampaignBulkPricing;
use NowCampaignStorefronts\Pricing\CampaignPriceResolver;
use NowCampaignStorefronts\Pricing\CartPriceApplier;
use NowCampaignStorefronts\Product\ProductAdapter;
use NowCampaignStorefronts\Reporting\CampaignDetailedReportService;
use NowCampaignStorefronts\Reporting\CampaignReportCache;
use NowCampaignStorefronts\Reporting\CampaignReportController;
use NowCampaignStorefronts\Reporting\CampaignReportPostType;
use NowCampaignStorefronts\Reporting\CampaignReportSecret;
use NowCampaignStorefronts\Reporting\CampaignReportService;
use NowCampaignStorefronts\Reporting\CampaignReportShare;
use NowCampaignStorefronts\Storefront\Assets as StorefrontAssets;
use NowCampaignStorefronts\Storefront\BulkPricingNotice;
use NowCampaignStorefronts\Storefront\CampaignRenderer;
use NowCampaignStorefronts\Storefront\CampaignSectionRenderer;
use NowCampaignStorefronts\Storefront\MiniCart;
use NowCampaignStorefronts\Storefront\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static ?Plugin $instance = null;
	private bool $initialized = false;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		( new Migrator() )->maybeMigrate();

		$postType = new PostType();
		$postType->register();
		( new Meta() )->register();
		( new CampaignReportPostType() )->register();

		$campaigns = new CampaignRepository();
		$campaignService = new CampaignService( $campaigns );
		$campaignProducts = new CampaignProductRepository();
		$campaignSections = new CampaignSectionRepository();
		$products = new ProductAdapter();
		$campaignProductService = new CampaignProductService( $campaignProducts, $products, $campaigns );
		$campaignSectionService = new CampaignSectionService( $campaignSections, $campaigns );
		$priceResolver = new CampaignPriceResolver( $campaigns, $campaignProducts );
		$bulkPricing = new CampaignBulkPricing();
		$cart = new CartService( $priceResolver, $campaignProducts, $products, $campaigns );
		$reports = new CampaignReportService();
		$detailedReports = new CampaignDetailedReportService( $reports );
		$reportCache = new CampaignReportCache( $detailedReports );
		$reportSecret = new CampaignReportSecret();
		$reportShare = new CampaignReportShare( $reportSecret );
		$reportController = new CampaignReportController( $reportShare, $reportCache );
		$campaignDuplicator = new CampaignDuplicator( $campaigns, $campaignSections, $campaignProducts, $reportShare );

		( new CartPriceApplier( $priceResolver, $bulkPricing ) )->register();
		( new CartValidator( $priceResolver, $products ) )->register();
		( new AjaxController( $cart ) )->register();
		( new OrderAttribution() )->register();
		( new OrderCampaignIndex() )->register();
		$reportCache->register();
		$reportController->register();

		$storefrontAssets = new StorefrontAssets();
		$storefrontAssets->register();
		$miniCart = new MiniCart( $cart, $storefrontAssets );
		$presentationResolver = new CampaignProductPresentationResolver( $products );
		$sectionRenderer = new CampaignSectionRenderer( $campaignSections, $campaignProducts, $products, $presentationResolver );
		$bulkPricingNotice = new BulkPricingNotice( $bulkPricing );
		( new Shortcodes( $campaigns, $sectionRenderer, $bulkPricingNotice, $miniCart, $storefrontAssets ) )->register();
		( new CampaignRenderer() )->register();

		// Bricks theme bootstraps after plugins_loaded and applies
		// bricks/dynamic_data/register_providers during its load, before init.
		// Register the NOW Campaign Storefronts provider key here so Bricks picks it up.
		add_filter( 'bricks/dynamic_data/register_providers', static function ( array $providers ): array {
			if ( ! in_array( 'nowcastf', $providers, true ) ) {
				$providers[] = 'nowcastf';
			}
			return $providers;
		} );

		// Bricks theme is a theme: by init its constants are defined. The
		// integration self-gates on BRICKS_VERSION.
		add_action( 'init', static function() use ( $campaignProducts, $presentationResolver ): void {
			( new BricksIntegration( $campaignProducts, $presentationResolver ) )->register();
		} );

		if ( is_admin() ) {
			( new CampaignList( $campaignService, $campaignProducts ) )->register();
			( new CampaignEditor(
				$campaigns,
				$campaignService,
				$campaignSections,
				$campaignSectionService,
				$campaignProducts,
				$campaignProductService,
				$products,
				$reports
			) )->register();
			( new CampaignPresentation( $campaignSections ) )->register();
			( new CampaignBulkPricingAdmin() )->register();
			( new CampaignReportAdmin( $reportShare, $reports ) )->register();
			( new CampaignDuplicateAdmin( $campaignDuplicator ) )->register();
		}

		add_action( 'deleted_post', function( int $postId, \WP_Post $post ) use ( $campaignProductService, $campaignSectionService, $reportCache, $reportShare ): void {
			if ( PostType::TYPE === $post->post_type ) {
				global $wpdb;
				if ( false === $wpdb->query( 'START TRANSACTION' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					error_log( 'NOW Campaign Storefronts cleanup failed: unable to start transaction.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					return;
				}
				try {
					$campaignProductService->deleteCampaignProducts( $postId );
					$campaignSectionService->deleteCampaignSections( $postId );
					if ( false === $wpdb->query( 'COMMIT' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						throw new \RuntimeException( 'Unable to commit Campaign cleanup.' );
					}
				} catch ( \Throwable $error ) {
					$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					error_log( 'NOW Campaign Storefronts cleanup failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$reportCache->invalidateCampaign( $postId );
				$reportShare->deleteForCampaign( $postId );
			}
		}, 10, 2 );

		do_action( 'nowcastf_loaded', $campaignService, $campaignProducts, $reports );
	}
}
