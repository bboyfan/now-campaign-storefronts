<?php

namespace WooCampaign\Storefront;

use WooCampaign\Pricing\CampaignBulkPricing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BulkPricingNotice {
	public function __construct( private CampaignBulkPricing $bulkPricing ) {}

	public function render( int $campaignId ): string {
		$config = $this->bulkPricing->config( $campaignId );
		$tiers = ! empty( $config['enabled'] ) ? (array) ( $config['tiers'] ?? [] ) : [];
		if ( ! $tiers ) {
			return '';
		}

		$title = trim( (string) ( $config['notice_title'] ?? '' ) );
		$description = trim( (string) ( $config['notice_description'] ?? '' ) );
		$title = '' !== $title ? $title : __( 'Campaign mix-and-match savings', 'now-campaign-storefronts' );
		$description = '' !== $description ? $description : __( 'Products and variations in this campaign are counted together and discounts apply automatically when a tier is reached.', 'now-campaign-storefronts' );

		ob_start();
		?>
		<div class="woo-campaign-bulk-pricing-notice" data-woo-campaign-bulk-pricing>
			<div class="woo-campaign-bulk-pricing-copy">
				<strong><?php echo esc_html( $title ); ?></strong>
				<span><?php echo esc_html( $description ); ?></span>
			</div>
			<div class="woo-campaign-bulk-pricing-tiers">
				<?php foreach ( $tiers as $tier ) : ?>
					<span class="woo-campaign-bulk-pricing-tier">
						<strong><?php echo esc_html( sprintf( __( '%d items or more', 'now-campaign-storefronts' ), absint( $tier['min_qty'] ?? 0 ) ) ); ?></strong>
						<small><?php echo esc_html( sprintf( __( 'Save %s%%', 'now-campaign-storefronts' ), wc_format_localized_decimal( (float) ( $tier['discount_percent'] ?? 0 ) ) ) ); ?></small>
					</span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
