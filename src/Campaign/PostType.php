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
					'name'          => __( 'Campaigns', 'wc-campaign' ),
					'singular_name' => __( 'Campaign', 'wc-campaign' ),
					'add_new_item'  => __( 'Add New Campaign', 'wc-campaign' ),
					'edit_item'     => __( 'Edit Campaign', 'wc-campaign' ),
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
