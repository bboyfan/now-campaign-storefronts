<?php

namespace Bboyfan\NowCampaignStorefronts\CampaignSection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Table {
	public static function name(): string {
		global $wpdb;
		return $wpdb->prefix . 'nowcastf_sections';
	}
}
