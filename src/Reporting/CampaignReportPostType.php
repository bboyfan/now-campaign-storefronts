<?php

namespace Bboyfan\NowCampaignStorefronts\Reporting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Internal post type used only as WordPress Core's password authority for a
 * Campaign external report. It deliberately has no public URL or admin UI.
 */
final class CampaignReportPostType {
	public const TYPE = 'nowcastf_report';

	public function register(): void {
		add_action( 'init', [ $this, 'registerPostType' ], 8 );
	}

	public function registerPostType(): void {
		register_post_type(
			self::TYPE,
			[
				'labels'              => [
					'name'          => __( 'Campaign Report Passwords', 'now-campaign-storefronts' ),
					'singular_name' => __( 'Campaign Report Password', 'now-campaign-storefronts' ),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'supports'            => [],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'delete_with_user'    => false,
			]
		);
	}
}
