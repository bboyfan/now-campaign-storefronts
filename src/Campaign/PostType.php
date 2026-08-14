<?php

namespace WooCampaign\Campaign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostType {
	public const TYPE = 'woo_campaign';

	public function register(): void {
		add_action( 'init', [ $this, 'registerPostType' ] );
	}

	public function registerPostType(): void {
		register_post_type(
			self::TYPE,
			[
				'labels' => [
					'name'          => __( 'Campaigns', 'now-campaign-storefronts' ),
					'singular_name' => __( 'Campaign', 'now-campaign-storefronts' ),
					'add_new_item'  => __( 'Add New Campaign', 'now-campaign-storefronts' ),
					'edit_item'     => __( 'Edit Campaign', 'now-campaign-storefronts' ),
				],
				'public'       => true,
				'show_ui'      => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-megaphone',
				'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
				'rewrite'      => [ 'slug' => 'campaign', 'with_front' => false ],
				'has_archive'  => false,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			],
		);
	}
}
