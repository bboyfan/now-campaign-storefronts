<?php

namespace WooCampaign\CampaignSection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Table {
	public static function name(): string {
		global $wpdb;
		return $wpdb->prefix . 'woo_campaign_sections';
	}
}
