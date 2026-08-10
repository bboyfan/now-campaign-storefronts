<?php

namespace WooCampaign;

use WooCampaign\Admin\CampaignBulkPricing as CampaignBulkPricingAdmin;
use WooCampaign\Admin\CampaignDuplicate as CampaignDuplicateAdmin;
use WooCampaign\Admin\CampaignEditor;
use WooCampaign\Admin\CampaignList;
use WooCampaign\Admin\CampaignPresentation;
use WooCampaign\Admin\CampaignReportAdmin;
use WooCampaign\Campaign\CampaignDuplicator;
use WooCampaign\Campaign\CampaignRepository;
use WooCampaign\Campaign\CampaignService;
use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;
use WooCampaign\CampaignProduct\Repository as CampaignProductRepository;
use WooCampaign\CampaignProduct\Service as CampaignProductService;
use WooCampaign\CampaignSection\Repository as CampaignSectionRepository;
use WooCampaign\CampaignSection\Service as CampaignSectionService;
use WooCampaign\Cart\AjaxController;
use WooCampaign\Cart\CartService;
use WooCampaign\Cart\CartValidator;
use WooCampaign\Install\Migrator;
use WooCampaign\Order\OrderAttribution;
use WooCampaign\Order\OrderCampaignIndex;
use WooCampaign\Pricing\CampaignBulkPricing;
use WooCampaign\Pricing\CampaignPriceResolver;
use WooCampaign\Pricing\CartPriceApplier;
use WooCampaign\Product\ProductAdapter;
use WooCampaign\Reporting\CampaignDetailedReportService;
use WooCampaign\Reporting\CampaignReportCache;
use WooCampaign\Reporting\CampaignReportController;
use WooCampaign\Reporting\CampaignReportPostType;
use WooCampaign\Reporting\CampaignReportSecret;
use WooCampaign\Reporting\CampaignReportService;
use WooCampaign\Reporting\CampaignReportShare;
use WooCampaign\Storefront\Assets as StorefrontAssets;
use WooCampaign\Storefront\BulkPricingNotice;
use WooCampaign\Storefront\CampaignRenderer;
use WooCampaign\Storefront\CampaignSectionRenderer;
use WooCampaign\Storefront\MiniCart;
use WooCampaign\Storefront\Shortcodes;

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
		$sectionRenderer = new CampaignSectionRenderer( $campaignSections, $campaignProducts, $products );
		$bulkPricingNotice = new BulkPricingNotice( $bulkPricing );
		( new Shortcodes( $campaigns, $sectionRenderer, $bulkPricingNotice, $miniCart, $storefrontAssets ) )->register();
		( new CampaignRenderer() )->register();

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
					error_log( 'WC Campaign cleanup failed: unable to start transaction.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
					error_log( 'WC Campaign cleanup failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$reportCache->invalidateCampaign( $postId );
				$reportShare->deleteForCampaign( $postId );
			}
		}, 10, 2 );

		do_action( 'woo_campaign_loaded', $campaignService, $campaignProducts, $reports );
	}
}
