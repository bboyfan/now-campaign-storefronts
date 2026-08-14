<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\CampaignRepository;
use WooCampaign\Campaign\CampaignService;
use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;
use WooCampaign\CampaignProduct\Repository as CampaignProductRepository;
use WooCampaign\CampaignProduct\Service as CampaignProductService;
use WooCampaign\CampaignSection\CampaignSection;
use WooCampaign\CampaignSection\Repository as CampaignSectionRepository;
use WooCampaign\CampaignSection\Service as CampaignSectionService;
use WooCampaign\Product\ProductAdapter;
use WooCampaign\Reporting\CampaignReportService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignEditor {
	private const PAGE_SLUG = 'woo-campaign-editor';
	private const NONCE_ACTION = 'woo_campaign_editor_save';
	private const AJAX_NONCE_ACTION = 'woo_campaign_editor_ajax';
	private const CREATE_ACTION = 'woo_campaign_create_editor';

	public function __construct(
		private CampaignRepository $campaigns,
		private CampaignService $campaignService,
		private CampaignSectionRepository $sections,
		private CampaignSectionService $sectionService,
		private CampaignProductRepository $campaignProducts,
		private CampaignProductService $campaignProductService,
		private ProductAdapter $products,
		private CampaignReportService $reports,
	) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'registerPage' ] );
		add_action( 'admin_init', [ $this, 'redirectNativeEditor' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_post_woo_campaign_save_editor', [ $this, 'save' ] );
		add_action( 'admin_post_' . self::CREATE_ACTION, [ $this, 'create' ] );
		add_action( 'wp_ajax_woo_campaign_editor_product_details', [ $this, 'ajaxProductDetails' ] );
		add_filter( 'get_edit_post_link', [ $this, 'filterEditLink' ], 10, 3 );
		add_filter( 'post_row_actions', [ $this, 'filterRowActions' ], 10, 2 );
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disableBlockEditor' ], 10, 2 );
	}

	public function registerPage(): void {
		add_submenu_page(
			null,
			__( 'Campaign Editor', 'now-campaign-storefronts' ),
			__( 'Campaign Editor', 'now-campaign-storefronts' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			[ $this, 'render' ]
		);
	}

	public function disableBlockEditor( bool $useBlockEditor, string $postType ): bool {
		return PostType::TYPE === $postType ? false : $useBlockEditor;
	}

	public function redirectNativeEditor(): void {
		global $pagenow;
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( 'post-new.php' === $pagenow && PostType::TYPE === sanitize_key( (string) ( $_GET['post_type'] ?? '' ) ) ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::PAGE_SLUG, 'new' => '1' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( 'post.php' !== $pagenow || 'edit' !== sanitize_key( (string) ( $_GET['action'] ?? '' ) ) ) {
			return;
		}
		$postId = absint( $_GET['post'] ?? 0 );
		if ( $postId > 0 && PostType::TYPE === get_post_type( $postId ) ) {
			wp_safe_redirect( $this->editorUrl( $postId ) );
			exit;
		}
	}

	public function filterEditLink( string $link, int $postId, string $context ): string {
		if ( PostType::TYPE !== get_post_type( $postId ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return $link;
		}
		return $this->editorUrl( $postId );
	}

	public function filterRowActions( array $actions, \WP_Post $post ): array {
		if ( PostType::TYPE !== $post->post_type ) {
			return $actions;
		}
		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = '<a href="' . esc_url( $this->editorUrl( $post->ID ) ) . '">' . esc_html__( 'Edit Campaign', 'now-campaign-storefronts' ) . '</a>';
		}
		return $actions;
	}

	public function enqueue( string $hook ): void {
		$page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
		if ( self::PAGE_SLUG !== $page && 'woo_campaign_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'woo-campaign-editor', WOO_CAMPAIGN_URL . 'assets/css/campaign-editor.css', [], WOO_CAMPAIGN_VERSION );
		wp_enqueue_script( 'woo-campaign-editor', WOO_CAMPAIGN_URL . 'assets/js/campaign-editor.js', [ 'jquery', 'wc-enhanced-select' ], WOO_CAMPAIGN_VERSION, true );

		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( $campaignId <= 0 ) {
			return;
		}
		wp_localize_script( 'woo-campaign-editor', 'WooCampaignEditor', $this->editorState( $campaignId ) );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage campaigns.', 'now-campaign-storefronts' ) );
		}
		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( ! $campaignId && ! empty( $_GET['new'] ) ) {
			$this->renderCreateForm();
			return;
		}
		$campaign = $this->campaigns->find( $campaignId );
		if ( ! $campaign ) {
			wp_die( esc_html__( 'Campaign not found.', 'now-campaign-storefronts' ) );
		}

		$report = $this->reports->report( $campaignId );
		$status = $this->campaignService->statusLabel( $campaignId );
		$start = (int) get_post_meta( $campaignId, Meta::START_AT, true );
		$end = (int) get_post_meta( $campaignId, Meta::END_AT, true );
		$archived = (bool) get_post_meta( $campaignId, Meta::ARCHIVED, true );
		?>
		<div class="wrap woo-campaign-editor-shell">
			<form id="woo-campaign-editor-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="woo_campaign_save_editor">
				<input type="hidden" name="campaign_id" value="<?php echo esc_attr( (string) $campaignId ); ?>">
				<input type="hidden" name="campaign_revision" value="<?php echo esc_attr( (string) absint( get_post_meta( $campaignId, Meta::EDITOR_REVISION, true ) ) ); ?>">
				<input type="hidden" name="campaign_modified_gmt" value="<?php echo esc_attr( $campaign->post_modified_gmt ); ?>">
				<input type="hidden" name="sections_json" id="woo-campaign-sections-json" value="">
				<input type="hidden" name="products_json" id="woo-campaign-products-json" value="">
				<?php wp_nonce_field( self::NONCE_ACTION, 'woo_campaign_editor_nonce' ); ?>

				<header class="woo-campaign-editor-topbar">
					<div class="woo-campaign-editor-titlebar">
						<a class="woo-campaign-editor-back" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . PostType::TYPE ) ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span><?php esc_html_e( 'Campaigns', 'now-campaign-storefronts' ); ?></a>
						<div>
							<div class="woo-campaign-editor-kicker"><?php esc_html_e( 'Campaign', 'now-campaign-storefronts' ); ?> · #<?php echo esc_html( (string) $campaignId ); ?></div>
							<h1><?php echo esc_html( $campaign->post_title ); ?></h1>
						</div>
						<span class="woo-campaign-editor-status is-<?php echo esc_attr( $status ); ?>"><span></span><?php echo esc_html( ucfirst( $status ) ); ?></span>
					</div>
					<div class="woo-campaign-editor-actions">
						<?php if ( 'publish' === $campaign->post_status && ! $archived ) : ?><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( get_permalink( $campaignId ) ); ?>"><?php esc_html_e( 'Preview', 'now-campaign-storefronts' ); ?></a><?php endif; ?>
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Campaign', 'now-campaign-storefronts' ); ?></button>
					</div>
				</header>

				<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Campaign saved.', 'now-campaign-storefronts' ); ?></p></div><?php endif; ?>

				<div class="woo-campaign-editor-layout">
					<main class="woo-campaign-editor-main">
						<section class="woo-campaign-editor-card">
							<div class="woo-campaign-editor-card-heading">
								<div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Campaign information', 'now-campaign-storefronts' ); ?></span><h2><?php esc_html_e( 'Basic information', 'now-campaign-storefronts' ); ?></h2></div>
								<p><?php esc_html_e( 'Set the campaign name and URL. Product content is managed in the Section Builder below.', 'now-campaign-storefronts' ); ?></p>
							</div>
							<div class="woo-campaign-editor-fields two-col">
								<label><span><?php esc_html_e( 'Campaign name', 'now-campaign-storefronts' ); ?></span><input type="text" name="campaign_title" value="<?php echo esc_attr( $campaign->post_title ); ?>" required></label>
								<label><span><?php esc_html_e( 'URL slug', 'now-campaign-storefronts' ); ?></span><div class="woo-campaign-slug-field"><code>/campaign/</code><input type="text" name="campaign_slug" value="<?php echo esc_attr( $campaign->post_name ); ?>"></div></label>
							</div>
						</section>

						<section class="woo-campaign-editor-card woo-campaign-content-card" data-campaign-content-card>
							<div class="woo-campaign-editor-card-heading">
								<div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Campaign content', 'now-campaign-storefronts' ); ?></span><h2><?php esc_html_e( 'Campaign content', 'now-campaign-storefronts' ); ?></h2></div>
								<p><?php esc_html_e( 'Set campaign images and introduction content.', 'now-campaign-storefronts' ); ?></p>
							</div>
							<label class="woo-campaign-editor-field"><span><?php esc_html_e( 'Campaign introduction', 'now-campaign-storefronts' ); ?></span><textarea name="campaign_description" rows="5" placeholder="<?php esc_attr_e( 'Describe this campaign, its offer, or important purchase notes.', 'now-campaign-storefronts' ); ?>"><?php echo esc_textarea( $campaign->post_content ); ?></textarea></label>
						</section>

						<section class="woo-campaign-editor-card woo-campaign-design-card" data-campaign-design-card>
							<div class="woo-campaign-editor-card-heading">
								<div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Page display', 'now-campaign-storefronts' ); ?></span><h2><?php esc_html_e( 'Campaign Design', 'now-campaign-storefronts' ); ?></h2></div>
								<p><?php esc_html_e( 'Fields that are not set inherit from the active theme.', 'now-campaign-storefronts' ); ?></p>
							</div>
							<div data-campaign-design-content></div>
						</section>

						<section class="woo-campaign-editor-card woo-campaign-sections-card">
							<div class="woo-campaign-editor-card-heading with-action">
								<div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Page builder', 'now-campaign-storefronts' ); ?></span><h2><?php esc_html_e( 'Product sections', 'now-campaign-storefronts' ); ?></h2><p><?php esc_html_e( 'Build the campaign page with sections. Each section can have its own layout, image, copy, and products.', 'now-campaign-storefronts' ); ?></p></div>
								<button type="button" class="button button-primary" data-woo-campaign-add-section><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Add product section', 'now-campaign-storefronts' ); ?></button>
							</div>
							<div id="woo-campaign-sections-builder" class="woo-campaign-sections-builder"></div>
						</section>
					</main>

					<aside class="woo-campaign-editor-sidebar">
						<section class="woo-campaign-editor-card sticky-card">
							<div class="woo-campaign-editor-card-heading compact"><div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Lifecycle', 'now-campaign-storefronts' ); ?></span><h2><?php esc_html_e( 'Publish settings', 'now-campaign-storefronts' ); ?></h2></div></div>
							<label class="woo-campaign-editor-field"><span><?php esc_html_e( 'Status', 'now-campaign-storefronts' ); ?></span><select name="campaign_post_status"><option value="draft" <?php selected( $campaign->post_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'now-campaign-storefronts' ); ?></option><option value="publish" <?php selected( $campaign->post_status, 'publish' ); ?>><?php esc_html_e( 'Published', 'now-campaign-storefronts' ); ?></option></select></label>
							<label class="woo-campaign-editor-field"><span><?php esc_html_e( 'Start time', 'now-campaign-storefronts' ); ?></span><input type="datetime-local" name="campaign_start_at" value="<?php echo esc_attr( $this->formatTimestamp( $start ) ); ?>"></label>
							<label class="woo-campaign-editor-field"><span><?php esc_html_e( 'End time', 'now-campaign-storefronts' ); ?></span><input type="datetime-local" name="campaign_end_at" value="<?php echo esc_attr( $this->formatTimestamp( $end ) ); ?>"></label>
							<label class="woo-campaign-editor-archive"><input type="checkbox" name="campaign_archived" value="1" <?php checked( $archived ); ?>><span><strong><?php esc_html_e( 'Archive campaign', 'now-campaign-storefronts' ); ?></strong><small><?php esc_html_e( 'Archived campaigns no longer allow checkout at campaign prices, while historical order attribution is preserved.', 'now-campaign-storefronts' ); ?></small></span></label>
						</section>

						<section class="woo-campaign-editor-card">
							<div class="woo-campaign-editor-card-heading compact"><div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Performance', 'now-campaign-storefronts' ); ?></span><h2><?php esc_html_e( 'Campaign performance', 'now-campaign-storefronts' ); ?></h2></div></div>
							<div class="woo-campaign-editor-net-sales"><span><?php esc_html_e( 'Net sales', 'now-campaign-storefronts' ); ?></span><strong><?php echo wp_kses_post( wc_price( $report['net_sales'] ) ); ?></strong></div>
							<div class="woo-campaign-editor-metrics">
								<div><span><?php esc_html_e( 'Paid orders', 'now-campaign-storefronts' ); ?></span><strong><?php echo esc_html( number_format_i18n( $report['orders'] ) ); ?></strong></div>
								<div><span><?php esc_html_e( 'Units', 'now-campaign-storefronts' ); ?></span><strong><?php echo esc_html( number_format_i18n( $report['units'] ) ); ?></strong></div>
								<div><span><?php esc_html_e( 'Discount', 'now-campaign-storefronts' ); ?></span><strong><?php echo wp_kses_post( wc_price( $report['discount'] ) ); ?></strong></div>
								<div><span><?php esc_html_e( 'Refund', 'now-campaign-storefronts' ); ?></span><strong><?php echo wp_kses_post( wc_price( $report['refund'] ) ); ?></strong></div>
							</div>
						</section>
					</aside>
				</div>
			</form>

			<div class="woo-campaign-product-modal" data-woo-campaign-product-modal hidden>
				<div class="woo-campaign-product-modal-backdrop" data-woo-campaign-product-modal-close></div>
				<div class="woo-campaign-product-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="woo-campaign-product-modal-title">
					<header><div><span class="woo-campaign-editor-eyebrow"><?php esc_html_e( 'Add product', 'now-campaign-storefronts' ); ?></span><h2 id="woo-campaign-product-modal-title"><?php esc_html_e( 'Select WooCommerce products', 'now-campaign-storefronts' ); ?></h2></div><button type="button" class="button-link" data-woo-campaign-product-modal-close aria-label="<?php esc_attr_e( 'Close', 'now-campaign-storefronts' ); ?>"><span class="dashicons dashicons-no-alt"></span></button></header>
					<div class="woo-campaign-product-modal-body">
						<label class="woo-campaign-editor-field"><span><?php esc_html_e( 'Search products', 'now-campaign-storefronts' ); ?></span><select class="wc-product-search" style="width:100%" data-placeholder="<?php esc_attr_e( 'Search products…', 'now-campaign-storefronts' ); ?>" data-action="woocommerce_json_search_products" data-allow_clear="true" data-woo-campaign-product-search></select></label>
						<div data-woo-campaign-product-picker-result class="woo-campaign-product-picker-result"></div>
					</div>
					<footer><button type="button" class="button" data-woo-campaign-product-modal-close><?php esc_html_e( 'Cancel', 'now-campaign-storefronts' ); ?></button><button type="button" class="button button-primary" data-woo-campaign-product-confirm disabled><?php esc_html_e( 'Add selected', 'now-campaign-storefronts' ); ?></button></footer>
				</div>
			</div>
		</div>
		<?php
	}

	public function create(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage campaigns.', 'now-campaign-storefronts' ) );
		}
		check_admin_referer( self::CREATE_ACTION, 'woo_campaign_create_nonce' );
		$campaignId = wp_insert_post(
			[
				'post_type'   => PostType::TYPE,
				'post_title'  => __( 'Untitled Campaign', 'now-campaign-storefronts' ),
				'post_status' => 'auto-draft',
			],
			true
		);
		if ( is_wp_error( $campaignId ) ) {
			wp_die( esc_html( $campaignId->get_error_message() ), '', [ 'response' => 500 ] );
		}
		wp_safe_redirect( $this->editorUrl( (int) $campaignId ) );
		exit;
	}

	private function renderCreateForm(): void {
		?>
		<div class="wrap woo-campaign-editor-shell">
			<h1><?php esc_html_e( 'Create Campaign', 'now-campaign-storefronts' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::CREATE_ACTION ); ?>">
				<?php wp_nonce_field( self::CREATE_ACTION, 'woo_campaign_create_nonce' ); ?>
				<?php submit_button( __( 'Create Campaign', 'now-campaign-storefronts' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage campaigns.', 'now-campaign-storefronts' ) );
		}
		check_admin_referer( self::NONCE_ACTION, 'woo_campaign_editor_nonce' );
		$campaignId = absint( $_POST['campaign_id'] ?? 0 );
		$campaign = $this->campaigns->find( $campaignId );
		if ( ! $campaign ) {
			wp_die( esc_html__( 'Campaign not found.', 'now-campaign-storefronts' ) );
		}
		$currentRevision = absint( get_post_meta( $campaignId, Meta::EDITOR_REVISION, true ) );
		$postedRevision = absint( $_POST['campaign_revision'] ?? 0 );
		$postedModifiedGmt = sanitize_text_field( wp_unslash( $_POST['campaign_modified_gmt'] ?? '' ) );
		if ( $postedRevision !== $currentRevision || $postedModifiedGmt !== $campaign->post_modified_gmt ) {
			$this->staleEditorDie();
		}

		$title = sanitize_text_field( wp_unslash( $_POST['campaign_title'] ?? '' ) );
		$slug = sanitize_title( wp_unslash( $_POST['campaign_slug'] ?? '' ) );
		$content = wp_kses_post( wp_unslash( $_POST['campaign_description'] ?? '' ) );
		$postStatus = 'publish' === sanitize_key( (string) ( $_POST['campaign_post_status'] ?? 'draft' ) ) ? 'publish' : 'draft';
		$sections = $this->decodeArray( wp_unslash( $_POST['sections_json'] ?? '[]' ) );
		$products = $this->decodeArray( wp_unslash( $_POST['products_json'] ?? '[]' ) );
		if ( isset( $_POST['section_design_json'] ) ) {
			$sectionDesign = $this->decodeArray( wp_unslash( (string) $_POST['section_design_json'] ) );
			foreach ( $sections as $index => &$section ) {
				$clientKey = sanitize_key( (string) ( $section['client_key'] ?? '' ) );
				$design = $sectionDesign[ $clientKey ] ?? $sectionDesign[ $index ] ?? [];
				$design = is_array( $design ) ? $design : [];
				$section['title_color'] = $design['title_color'] ?? '';
				$section['cta_bg_color'] = $design['cta_bg_color'] ?? '';
				$section['cta_text_color'] = $design['cta_text_color'] ?? '';
			}
			unset( $section );
		}

		$mediaIds = null;
		if ( isset( $_POST['campaign_media_ids'] ) ) {
			$mediaIds = $this->decodeArray( wp_unslash( (string) $_POST['campaign_media_ids'] ) );
			$mediaIds = array_values( array_unique( array_filter( array_map( 'absint', $mediaIds ) ) ) );
			$mediaIds = array_values( array_filter( $mediaIds, 'wp_attachment_is_image' ) );
		}
		$design = isset( $_POST['campaign_design_json'] )
			? Meta::sanitizeDesign( $this->decodeArray( wp_unslash( (string) $_POST['campaign_design_json'] ) ) )
			: null;

		try {
			$this->beginTransaction();
			$saveLock = $this->lockCampaignForSave( $campaignId );
			$currentRevision = $saveLock['revision'];
			if ( $postedRevision !== $currentRevision || $postedModifiedGmt !== $saveLock['post_modified_gmt'] ) {
				$this->rollbackTransaction();
				$this->staleEditorDie();
			}
			$updated = wp_update_post(
				[
					'ID'           => $campaignId,
					'post_title'   => $title ?: __( 'Untitled Campaign', 'now-campaign-storefronts' ),
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => $postStatus,
				],
				true
			);
			if ( is_wp_error( $updated ) ) {
				throw new \RuntimeException( $updated->get_error_message() );
			}

			$this->updateMeta( $campaignId, Meta::START_AT, $this->parseDate( sanitize_text_field( wp_unslash( $_POST['campaign_start_at'] ?? '' ) ) ) );
			$this->updateMeta( $campaignId, Meta::END_AT, $this->parseDate( sanitize_text_field( wp_unslash( $_POST['campaign_end_at'] ?? '' ) ) ) );
			$this->updateMeta( $campaignId, Meta::ARCHIVED, isset( $_POST['campaign_archived'] ) ? 1 : 0 );
			if ( null !== $mediaIds ) {
				$this->updateMeta( $campaignId, Meta::MEDIA_IDS, $mediaIds );
			}
			if ( null !== $design ) {
				$this->updateMeta( $campaignId, Meta::DESIGN, $design );
			}

			$keyMap = $this->sectionService->save( $campaignId, $sections, false );
			$fallbackSectionId = $keyMap ? (int) reset( $keyMap ) : $this->sectionService->ensureDefault( $campaignId );
			$productInput = [];
			foreach ( $products as $position => $product ) {
				$sectionKey = sanitize_key( (string) ( $product['section_key'] ?? '' ) );
				$productInput[] = [
					'saleable_id'    => absint( $product['saleable_id'] ?? 0 ),
					'section_id'     => (int) ( $keyMap[ $sectionKey ] ?? $fallbackSectionId ),
					'campaign_price' => $product['campaign_price'] ?? '',
					'campaign_copy'  => $product['campaign_copy'] ?? '',
					'status'         => $product['status'] ?? 'active',
					'display_order'  => isset( $product['display_order'] ) ? absint( $product['display_order'] ) : $position,
				];
			}
			$this->campaignProductService->replace( $campaignId, $productInput, false );
			$this->updateMeta( $campaignId, Meta::EDITOR_REVISION, $currentRevision + 1 );
			$this->commitTransaction();
		} catch ( \Throwable $error ) {
			$this->rollbackTransaction();
			clean_post_cache( $campaignId );
			error_log( 'NOW Campaign Storefronts save failed: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			wp_die(
				esc_html__( 'Campaign could not be saved. Reload the editor, verify the current values, and try again.', 'now-campaign-storefronts' ),
				esc_html__( 'Campaign save failed', 'now-campaign-storefronts' ),
				[ 'response' => 500, 'back_link' => true ]
			);
		}

		clean_post_cache( $campaignId );
		do_action( 'woo_campaign_sections_updated', $campaignId );
		do_action( 'woo_campaign_updated', $campaignId );

		wp_safe_redirect( add_query_arg( 'updated', '1', $this->editorUrl( $campaignId ) ) );
		exit;
	}

	public function ajaxProductDetails(): void {
		check_ajax_referer( self::AJAX_NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized', 'now-campaign-storefronts' ) ], 403 );
		}
		$productId = absint( $_POST['product_id'] ?? 0 );
		$product = wc_get_product( $productId );
		if ( ! $product ) {
			wp_send_json_error( [ 'message' => __( 'Product not found.', 'now-campaign-storefronts' ) ], 404 );
		}

		if ( $product->is_type( 'variable' ) ) {
			$variations = [];
			foreach ( $product->get_children() as $variationId ) {
				$variation = wc_get_product( $variationId );
				if ( ! $variation instanceof \WC_Product_Variation ) {
					continue;
				}
				$variations[] = $this->productPayload( $variation, $product );
			}
			wp_send_json_success( [ 'type' => 'variable', 'product' => $this->parentPayload( $product ), 'items' => $variations ] );
		}

		$normalized = $this->products->normalizeSaleable( $productId );
		if ( ! $normalized ) {
			wp_send_json_error( [ 'message' => __( 'This product cannot be used as a Campaign item.', 'now-campaign-storefronts' ) ], 400 );
		}
		wp_send_json_success( [ 'type' => 'simple', 'product' => $this->parentPayload( $product ), 'items' => [ $this->productPayload( $product, $product ) ] ] );
	}

	private function editorState( int $campaignId ): array {
		$sections = $this->sections->forCampaign( $campaignId );
		if ( ! $sections ) {
			$sections = [ new CampaignSection( 0, $campaignId, '', '', 0, CampaignSection::LAYOUT_QUICK_ORDER, 'active', 0 ) ];
		}
		$sectionState = [];
		foreach ( $sections as $index => $section ) {
			$key = $section->id > 0 ? 'section-' . $section->id : 'section-new-' . $index;
			$sectionState[] = [
				'id' => $section->id,
				'clientKey' => $key,
				'title' => $section->title,
				'description' => $section->description,
				'imageId' => $section->imageId,
				'imageUrl' => $section->imageId > 0 ? (string) wp_get_attachment_image_url( $section->imageId, 'medium_large' ) : '',
				'layout' => $section->layout,
				'status' => $section->status,
				'displayOrder' => $index,
			];
		}
		$sectionKeyById = [];
		foreach ( $sectionState as $section ) {
			if ( $section['id'] > 0 ) {
				$sectionKeyById[ $section['id'] ] = $section['clientKey'];
			}
		}
		$fallbackKey = $sectionState[0]['clientKey'];
		$productState = [];
		foreach ( $this->campaignProducts->forCampaign( $campaignId ) as $row ) {
			$product = $this->products->get( $row->saleableId() );
			if ( ! $product ) {
				continue;
			}
			$parent = $row->variationId > 0 ? $this->products->get( $row->productId ) : $product;
			$productState[] = [
				'id' => $row->id,
				'saleableId' => $row->saleableId(),
				'productId' => $row->productId,
				'variationId' => $row->variationId,
				'sectionKey' => $sectionKeyById[ $row->sectionId ] ?? $fallbackKey,
				'productName' => $parent ? $parent->get_name() : $product->get_name(),
				'variationName' => $product instanceof \WC_Product_Variation ? wc_get_formatted_variation( $product, true, false, true ) : '',
				'sku' => $product->get_sku(),
				'image' => $this->productImage( $product, $parent ),
				'wooPrice' => (float) $product->get_price( 'edit' ),
				'wooPriceHtml' => wc_price( (float) $product->get_price( 'edit' ) ),
				'stockHtml' => wc_get_stock_html( $product ),
				'campaignPrice' => wc_format_localized_price( rtrim( rtrim( wc_format_decimal( $row->campaignPrice, false ), '0' ), '.' ) ),
				'campaignCopy' => $row->campaignCopy,
				'status' => $row->status,
				'displayOrder' => $row->displayOrder,
			];
		}

		return [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( self::AJAX_NONCE_ACTION ),
			'campaignId' => $campaignId,
			'sections' => $sectionState,
			'products' => $productState,
			'layouts' => [
				'quick_order' => [ 'label' => 'Quick Order', 'description' => __( 'Expand products and variation quantities for fast multi-item ordering.', 'now-campaign-storefronts' ) ],
				'editorial' => [ 'label' => 'Editorial', 'description' => __( 'Prioritize larger imagery and copy for featured products.', 'now-campaign-storefronts' ) ],
				'compact_grid' => [ 'label' => 'Compact Grid', 'description' => __( 'Use a compact card grid for add-ons and smaller items.', 'now-campaign-storefronts' ) ],
			],
			'i18n' => [
				'untitledSection' => __( 'Untitled product section', 'now-campaign-storefronts' ),
				'noProducts' => __( 'This section does not have any products yet.', 'now-campaign-storefronts' ),
				'addProduct' => __( 'Add product', 'now-campaign-storefronts' ),
				'removeSectionConfirm' => __( 'Delete this section? Its products will move to the first remaining section.', 'now-campaign-storefronts' ),
				'duplicateProduct' => __( 'This product or variation is already in the campaign.', 'now-campaign-storefronts' ),
				'chooseProductsHelp' => __( 'Choose a simple or variable product from the WooCommerce product catalog.', 'now-campaign-storefronts' ),
				'moveUp' => __( 'Move up', 'now-campaign-storefronts' ),
				'moveDown' => __( 'Move down', 'now-campaign-storefronts' ),
				'deleteSection' => __( 'Delete section', 'now-campaign-storefronts' ),
				'sectionTitle' => __( 'Section title', 'now-campaign-storefronts' ),
				'sectionTitlePlaceholder' => __( 'For example: Mix and match fragrances', 'now-campaign-storefronts' ),
				'sectionDescription' => __( 'Section description', 'now-campaign-storefronts' ),
				'sectionDescriptionPlaceholder' => __( 'Describe this product section, offer, or purchase guidance.', 'now-campaign-storefronts' ),
				'sectionImage' => __( 'Section image', 'now-campaign-storefronts' ),
				'selectImage' => __( 'Select image', 'now-campaign-storefronts' ),
				'remove' => __( 'Remove', 'now-campaign-storefronts' ),
				'productLayout' => __( 'Product layout', 'now-campaign-storefronts' ),
				'productLayoutHelp' => __( 'The layout applies to the entire section to keep its presentation consistent.', 'now-campaign-storefronts' ),
				'products' => __( 'Products', 'now-campaign-storefronts' ),
				'productAuthorityHelp' => __( 'Campaign items store only campaign price, campaign copy, and display status. WooCommerce remains authoritative for name, image, and stock.', 'now-campaign-storefronts' ),
				'displayStatus' => __( 'Display status', 'now-campaign-storefronts' ),
				'visible' => __( 'Visible', 'now-campaign-storefronts' ),
				'hidden' => __( 'Hidden', 'now-campaign-storefronts' ),
				'campaignProductCopy' => __( 'Campaign product copy', 'now-campaign-storefronts' ),
				'campaignProductCopyPlaceholder' => __( 'Used only in this campaign and does not change the WooCommerce product description.', 'now-campaign-storefronts' ),
				'moveTo' => __( 'Move to', 'now-campaign-storefronts' ),
				'searchProductHelp' => __( 'Search for a WooCommerce product. Variable products list their variations.', 'now-campaign-storefronts' ),
				'loadingProduct' => __( 'Loading product data…', 'now-campaign-storefronts' ),
				'selectAllVariations' => __( 'Select all available variations', 'now-campaign-storefronts' ),
				'selectSectionImage' => __( 'Select section image', 'now-campaign-storefronts' ),
				'useImage' => __( 'Use this image', 'now-campaign-storefronts' ),
			],
		];
	}

	private function parentPayload( \WC_Product $product ): array {
		return [
			'id' => $product->get_id(),
			'name' => $product->get_name(),
			'image' => $this->productImage( $product, $product ),
		];
	}

	private function productPayload( \WC_Product $product, \WC_Product $parent ): array {
		return [
			'saleableId' => $product->get_id(),
			'productId' => $product instanceof \WC_Product_Variation ? $parent->get_id() : $product->get_id(),
			'variationId' => $product instanceof \WC_Product_Variation ? $product->get_id() : 0,
			'productName' => $parent->get_name(),
			'variationName' => $product instanceof \WC_Product_Variation ? wc_get_formatted_variation( $product, true, false, true ) : '',
			'sku' => $product->get_sku(),
			'image' => $this->productImage( $product, $parent ),
			'wooPrice' => (float) $product->get_price( 'edit' ),
			'wooPriceHtml' => wc_price( (float) $product->get_price( 'edit' ) ),
			'stockHtml' => wc_get_stock_html( $product ),
		];
	}

	private function productImage( \WC_Product $product, ?\WC_Product $parent ): string {
		$imageId = $product->get_image_id();
		if ( $imageId <= 0 && $parent ) {
			$imageId = $parent->get_image_id();
		}
		return $imageId > 0 ? (string) wp_get_attachment_image_url( $imageId, 'thumbnail' ) : '';
	}

	private function decodeArray( string $raw ): array {
		$decoded = json_decode( $raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			wp_die(
				esc_html__( 'The Campaign editor submitted invalid data. Reload the editor and try again.', 'now-campaign-storefronts' ),
				esc_html__( 'Invalid Campaign data', 'now-campaign-storefronts' ),
				[ 'response' => 400, 'back_link' => true ]
			);
		}
		return $decoded;
	}

	private function updateMeta( int $campaignId, string $key, mixed $value ): void {
		$result = update_post_meta( $campaignId, $key, $value );
		if ( false === $result && ! $this->metaValuesEqual( get_post_meta( $campaignId, $key, true ), $value ) ) {
			throw new \RuntimeException( 'Unable to update Campaign metadata: ' . $key );
		}
	}

	private function metaValuesEqual( mixed $stored, mixed $expected ): bool {
		if ( is_int( $expected ) || is_bool( $expected ) ) {
			return absint( $stored ) === absint( $expected );
		}
		if ( is_array( $expected ) ) {
			return maybe_serialize( $stored ) === maybe_serialize( $expected );
		}
		return (string) $stored === (string) $expected;
	}

	private function beginTransaction(): void {
		global $wpdb;
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			throw new \RuntimeException( 'Unable to start Campaign save transaction.' );
		}
	}

	private function lockCampaignForSave( int $campaignId ): array {
		global $wpdb;
		$lockedPost = $wpdb->get_row(
			$wpdb->prepare( "SELECT ID, post_modified_gmt FROM {$wpdb->posts} WHERE ID = %d FOR UPDATE", $campaignId ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		if ( ! is_array( $lockedPost ) || $campaignId !== (int) $lockedPost['ID'] ) {
			throw new \RuntimeException( 'Unable to lock Campaign for saving.' );
		}
		$revision = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1", $campaignId, Meta::EDITOR_REVISION ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		return [
			'revision'          => absint( $revision ),
			'post_modified_gmt' => (string) $lockedPost['post_modified_gmt'],
		];
	}

	private function commitTransaction(): void {
		global $wpdb;
		if ( false === $wpdb->query( 'COMMIT' ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			throw new \RuntimeException( 'Unable to commit Campaign changes.' );
		}
	}

	private function rollbackTransaction(): void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	private function staleEditorDie(): never {
		wp_die(
			esc_html__( 'This Campaign was updated in another tab or session. Reload the editor before saving again.', 'now-campaign-storefronts' ),
			esc_html__( 'Campaign changed', 'now-campaign-storefronts' ),
			[ 'response' => 409, 'back_link' => true ]
		);
	}

	private function editorUrl( int $campaignId ): string {
		return add_query_arg( [ 'page' => self::PAGE_SLUG, 'campaign_id' => $campaignId ], admin_url( 'admin.php' ) );
	}

	private function parseDate( string $value ): int {
		if ( '' === $value ) {
			return 0;
		}
		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', $value, wp_timezone() );
		return $date instanceof \DateTimeImmutable ? $date->getTimestamp() : 0;
	}

	private function formatTimestamp( int $timestamp ): string {
		return $timestamp > 0 ? wp_date( 'Y-m-d\\TH:i', $timestamp, wp_timezone() ) : '';
	}
}
