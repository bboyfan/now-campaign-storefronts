<?php

namespace Bboyfan\NowCampaignStorefronts;

use Bboyfan\NowCampaignStorefronts\Admin\CampaignBulkPricing as CampaignBulkPricingAdmin;
use Bboyfan\NowCampaignStorefronts\Admin\CampaignDuplicate as CampaignDuplicateAdmin;
use Bboyfan\NowCampaignStorefronts\Admin\CampaignEditor;
use Bboyfan\NowCampaignStorefronts\Admin\CampaignList;
use Bboyfan\NowCampaignStorefronts\Admin\CampaignPresentation;
use Bboyfan\NowCampaignStorefronts\Admin\CampaignReportAdmin;
use Bboyfan\NowCampaignStorefronts\Campaign\CampaignDuplicator;
use Bboyfan\NowCampaignStorefronts\Campaign\CampaignRepository;
use Bboyfan\NowCampaignStorefronts\Campaign\CampaignService;
use Bboyfan\NowCampaignStorefronts\Campaign\Meta;
use Bboyfan\NowCampaignStorefronts\Campaign\PostType;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\CampaignProductPresentationResolver;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\Repository as CampaignProductRepository;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\Service as CampaignProductService;
use Bboyfan\NowCampaignStorefronts\CampaignSection\Repository as CampaignSectionRepository;
use Bboyfan\NowCampaignStorefronts\CampaignSection\Service as CampaignSectionService;
use Bboyfan\NowCampaignStorefronts\Cart\AjaxController;
use Bboyfan\NowCampaignStorefronts\Cart\CartService;
use Bboyfan\NowCampaignStorefronts\Cart\CartValidator;
use Bboyfan\NowCampaignStorefronts\Install\Migrator;
use Bboyfan\NowCampaignStorefronts\Integration\Bricks\BricksIntegration;
use Bboyfan\NowCampaignStorefronts\Order\OrderAttribution;
use Bboyfan\NowCampaignStorefronts\Order\OrderCampaignIndex;
use Bboyfan\NowCampaignStorefronts\Pricing\CampaignBulkPricing;
use Bboyfan\NowCampaignStorefronts\Pricing\CampaignPriceResolver;
use Bboyfan\NowCampaignStorefronts\Pricing\CartPriceApplier;
use Bboyfan\NowCampaignStorefronts\Product\ProductAdapter;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignDetailedReportService;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportCache;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportController;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportPostType;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportSecret;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportService;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportShare;
use Bboyfan\NowCampaignStorefronts\Storefront\Assets as StorefrontAssets;
use Bboyfan\NowCampaignStorefronts\Storefront\BulkPricingNotice;
use Bboyfan\NowCampaignStorefronts\Storefront\CampaignRenderer;
use Bboyfan\NowCampaignStorefronts\Storefront\CampaignSectionRenderer;
use Bboyfan\NowCampaignStorefronts\Storefront\MiniCart;
use Bboyfan\NowCampaignStorefronts\Storefront\Shortcodes;

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
