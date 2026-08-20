<?php

namespace Bboyfan\NowCampaignStorefronts\Install;

use Bboyfan\NowCampaignStorefronts\Campaign\PostType;
use Bboyfan\NowCampaignStorefronts\Reporting\CampaignReportController;

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
