<?php

namespace WooCampaign\Install;

use WooCampaign\Campaign\PostType;
use WooCampaign\Reporting\CampaignReportController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {
	public static function activate(): void {
		( new Migrator() )->migrate();
		( new PostType() )->registerPostType();
		CampaignReportController::registerRewriteRules();
		flush_rewrite_rules();
	}
}
