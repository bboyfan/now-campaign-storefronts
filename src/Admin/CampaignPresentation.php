<?php

namespace WooCampaign\Admin;

use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;
use WooCampaign\CampaignSection\Repository as CampaignSectionRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignPresentation {
	private const PAGE_SLUG = 'woo-campaign-editor';
	private const EDITOR_NONCE_ACTION = 'woo_campaign_editor_save';

	public function __construct( private CampaignSectionRepository $sections ) {}

	public function register(): void {
		add_action( 'load-admin_page_' . self::PAGE_SLUG, [ $this, 'setAdminTitle' ] );
		add_action( 'load-woo_campaign_page_' . self::PAGE_SLUG, [ $this, 'setAdminTitle' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 20 );
		add_action( 'woo_campaign_updated', [ $this, 'persistSectionDesign' ], 20, 1 );
	}

	public function setAdminTitle(): void {
		global $title;
		$title = __( 'Campaign Editor', 'now-campaign-storefronts' );
	}

	public function enqueue(): void {
		if ( self::PAGE_SLUG !== sanitize_key( (string) ( $_GET['page'] ?? '' ) ) ) {
			return;
		}
		$campaignId = absint( $_GET['campaign_id'] ?? 0 );
		if ( $campaignId <= 0 || PostType::TYPE !== get_post_type( $campaignId ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_editor();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style(
			'woo-campaign-presentation-admin',
			WOO_CAMPAIGN_URL . 'assets/css/presentation-v2-admin.css',
			[ 'woo-campaign-editor' ],
			WOO_CAMPAIGN_VERSION
		);
		wp_enqueue_script(
			'woo-campaign-presentation-admin',
			WOO_CAMPAIGN_URL . 'assets/js/presentation-v2-admin.js',
			[ 'jquery', 'jquery-ui-sortable', 'woo-campaign-editor' ],
			WOO_CAMPAIGN_VERSION,
			true
		);
		wp_enqueue_style(
			'woo-campaign-editor-ux-fix',
			WOO_CAMPAIGN_URL . 'assets/css/campaign-editor-ux-fix.css',
			[ 'woo-campaign-presentation-admin' ],
			WOO_CAMPAIGN_VERSION
		);
		wp_enqueue_script(
			'woo-campaign-editor-ux-fix',
			WOO_CAMPAIGN_URL . 'assets/js/campaign-editor-ux-fix.js',
			[ 'jquery', 'woo-campaign-presentation-admin' ],
			WOO_CAMPAIGN_VERSION,
			true
		);
		wp_enqueue_style(
			'woo-campaign-presentation-consistency-admin',
			WOO_CAMPAIGN_URL . 'assets/css/presentation-v2-consistency-admin.css',
			[ 'woo-campaign-editor-ux-fix' ],
			WOO_CAMPAIGN_VERSION
		);
		wp_enqueue_script(
			'woo-campaign-presentation-consistency-admin',
			WOO_CAMPAIGN_URL . 'assets/js/presentation-v2-consistency-admin.js',
			[ 'jquery', 'woo-campaign-editor-ux-fix' ],
			WOO_CAMPAIGN_VERSION,
			true
		);

		wp_localize_script(
			'woo-campaign-presentation-admin',
			'WooCampaignPresentation',
			$this->state( $campaignId )
		);
	}

	public function persistSectionDesign( int $campaignId ): void {
		if ( ! isset( $_POST['section_design_json'], $_POST['sections_json'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( 'woo_campaign_save_editor' !== sanitize_key( (string) ( $_POST['action'] ?? '' ) ) || $campaignId !== absint( $_POST['campaign_id'] ?? 0 ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['woo_campaign_editor_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, self::EDITOR_NONCE_ACTION ) ) {
			return;
		}

		$postedSections = json_decode( wp_unslash( (string) $_POST['sections_json'] ), true );
		$designByKey = json_decode( wp_unslash( (string) $_POST['section_design_json'] ), true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $postedSections ) || ! is_array( $designByKey ) ) {
			return;
		}

		$savedSections = $this->sections->forCampaign( $campaignId );
		$savedById = [];
		foreach ( $savedSections as $savedSection ) {
			$savedById[ $savedSection->id ] = $savedSection;
		}

		foreach ( $postedSections as $index => $postedSection ) {
			if ( ! is_array( $postedSection ) ) {
				continue;
			}
			$clientKey = sanitize_key( (string) ( $postedSection['client_key'] ?? '' ) );
			$design = $designByKey[ $clientKey ] ?? $designByKey[ $index ] ?? null;
			if ( ! is_array( $design ) ) {
				continue;
			}

			$sectionId = absint( $postedSection['id'] ?? 0 );
			if ( $sectionId <= 0 || ! isset( $savedById[ $sectionId ] ) ) {
				$sectionId = isset( $savedSections[ $index ] ) ? (int) $savedSections[ $index ]->id : 0;
			}
			if ( $sectionId > 0 ) {
				$this->sections->updateDesign( $sectionId, $campaignId, $design );
			}
		}
	}

	private function state( int $campaignId ): array {
		$mediaIds = get_post_meta( $campaignId, Meta::MEDIA_IDS, true );
		$mediaIds = is_array( $mediaIds ) ? array_values( array_filter( array_map( 'absint', $mediaIds ) ) ) : [];
		$media = [];
		foreach ( $mediaIds as $id ) {
			if ( ! wp_attachment_is_image( $id ) ) {
				continue;
			}
			$thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
			$url = wp_get_attachment_image_url( $id, 'large' );
			if ( ! $thumb && ! $url ) {
				continue;
			}
			$media[] = [
				'id'    => $id,
				'thumb' => (string) ( $thumb ?: $url ),
				'url'   => (string) ( $url ?: $thumb ),
				'alt'   => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			];
		}

		$design = get_post_meta( $campaignId, Meta::DESIGN, true );
		$design = Meta::sanitizeDesign( is_array( $design ) ? $design : [] );
		$sectionDesign = [];
		foreach ( $this->sections->forCampaign( $campaignId ) as $section ) {
			$sectionDesign[ (string) $section->id ] = [
				'title_color'    => $section->titleColor,
				'copy_color'     => $section->copyColor,
				'cta_bg_color'   => $section->ctaBgColor,
				'cta_text_color' => $section->ctaTextColor,
			];
		}

		return [
			'campaignId' => $campaignId,
			'media'      => $media,
			'design'     => $design,
			'sections'   => $sectionDesign,
			'i18n'       => [
				'imagesTitle' => __( 'Campaign images', 'now-campaign-storefronts' ),
				'imagesHelp'  => __( 'Images are displayed above the campaign introduction in this order and use the campaign content width by default.', 'now-campaign-storefronts' ),
				'addImages'   => __( 'Add / select images', 'now-campaign-storefronts' ),
				'remove'      => __( 'Remove', 'now-campaign-storefronts' ),
				'removeImage' => __( 'Remove image', 'now-campaign-storefronts' ),
				'inherit'     => __( 'Not set', 'now-campaign-storefronts' ),
				'overridden'  => __( 'Set', 'now-campaign-storefronts' ),
				'emptyGallery' => __( 'No campaign images have been added yet.', 'now-campaign-storefronts' ),
				'introLabel'  => __( 'Campaign introduction', 'now-campaign-storefronts' ),
				'sectionDesignHelp' => __( 'Empty values inherit from the campaign or active theme.', 'now-campaign-storefronts' ),
			],
		];
	}
}
