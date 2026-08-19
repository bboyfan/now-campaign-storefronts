<?php
/** @var WP_Post $campaign */
/** @var WP_Post $reportPost */
/** @var int $campaignId */
/** @var string $key */
/** @var bool $authenticated */
/** @var array $presented */

use NowCampaignStorefronts\Campaign\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nowcastf_start = absint( get_post_meta( $campaignId, Meta::START_AT, true ) );
$nowcastf_end = absint( get_post_meta( $campaignId, Meta::END_AT, true ) );
$nowcastf_summary = $authenticated ? (array) ( $presented['summary'] ?? [] ) : [];
$nowcastf_formatted = $authenticated ? (array) ( $presented['formatted'] ?? [] ) : [];
$nowcastf_products = $authenticated ? (array) ( $presented['products'] ?? [] ) : [];
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow,noarchive">
	<title><?php echo esc_html( $campaign->post_title . ' — Campaign Report' ); ?></title>
	<?php wp_print_styles( [ 'nowcastf-report' ] ); ?>
</head>
<body class="nowcastf-report-body">
<main class="nowcastf-report-shell">
	<?php if ( ! $authenticated ) : ?>
		<section class="nowcastf-report-login" aria-labelledby="nowcastf-report-login-title">
			<div class="nowcastf-report-brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
			<p class="nowcastf-report-eyebrow"><?php esc_html_e( 'Campaign Report', 'now-campaign-storefronts' ); ?></p>
			<h1 id="nowcastf-report-login-title"><?php echo esc_html( $campaign->post_title ); ?></h1>
			<p class="nowcastf-report-login-copy"><?php esc_html_e( 'Enter the password to view live campaign performance.', 'now-campaign-storefronts' ); ?></p>
			<form method="post" action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>">
				<label>
					<span><?php esc_html_e( 'Password', 'now-campaign-storefronts' ); ?></span>
					<input type="password" name="post_password" autocomplete="current-password" autofocus required>
				</label>
				<button type="submit"><?php esc_html_e( 'View report', 'now-campaign-storefronts' ); ?></button>
			</form>
		</section>
	<?php else : ?>
		<header class="nowcastf-report-header">
			<div>
				<div class="nowcastf-report-brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
				<p class="nowcastf-report-eyebrow"><?php esc_html_e( 'Campaign Report', 'now-campaign-storefronts' ); ?></p>
				<h1><?php echo esc_html( $campaign->post_title ); ?></h1>
				<?php if ( $nowcastf_start || $nowcastf_end ) : ?>
					<p class="nowcastf-report-period">
						<?php if ( $nowcastf_start ) : ?><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $nowcastf_start ) ); ?><?php endif; ?>
						<?php if ( $nowcastf_start && $nowcastf_end ) : ?> <span aria-hidden="true">→</span> <?php endif; ?>
						<?php if ( $nowcastf_end ) : ?><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $nowcastf_end ) ); ?><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			<div class="nowcastf-report-live"><span></span><?php esc_html_e( 'Live', 'now-campaign-storefronts' ); ?> · <span data-report-updated><?php echo esc_html( (string) ( $presented['updated_label'] ?? '' ) ); ?></span></div>
		</header>

		<section class="nowcastf-report-hero-metric">
			<span><?php esc_html_e( 'Net Sales', 'now-campaign-storefronts' ); ?></span>
			<strong data-report-money="net_sales"><?php echo $nowcastf_formatted['net_sales'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
		</section>

		<section class="nowcastf-report-metrics" aria-label="<?php esc_attr_e( 'Campaign summary', 'now-campaign-storefronts' ); ?>">
			<div><span><?php esc_html_e( 'Paid Orders', 'now-campaign-storefronts' ); ?></span><strong data-report-value="orders"><?php echo esc_html( number_format_i18n( (int) ( $nowcastf_summary['orders'] ?? 0 ) ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Units', 'now-campaign-storefronts' ); ?></span><strong data-report-value="units"><?php echo esc_html( number_format_i18n( (int) ( $nowcastf_summary['units'] ?? 0 ) ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Average Order', 'now-campaign-storefronts' ); ?></span><strong data-report-money="average_order"><?php echo $nowcastf_formatted['average_order'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Pending Orders', 'now-campaign-storefronts' ); ?></span><strong data-report-value="pending_orders"><?php echo esc_html( number_format_i18n( (int) ( $nowcastf_summary['pending_orders'] ?? 0 ) ) ); ?></strong></div>
		</section>

		<section class="nowcastf-report-breakdown">
			<div><span><?php esc_html_e( 'Campaign Subtotal', 'now-campaign-storefronts' ); ?></span><strong data-report-money="campaign_subtotal"><?php echo $nowcastf_formatted['campaign_subtotal'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Discount', 'now-campaign-storefronts' ); ?></span><strong data-report-money="discount"><?php echo $nowcastf_formatted['discount'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Refund', 'now-campaign-storefronts' ); ?></span><strong data-report-money="refund"><?php echo $nowcastf_formatted['refund'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Refunded Units', 'now-campaign-storefronts' ); ?></span><strong data-report-value="refunded_units"><?php echo esc_html( number_format_i18n( (int) ( $nowcastf_summary['refunded_units'] ?? 0 ) ) ); ?></strong></div>
		</section>

		<section class="nowcastf-report-products">
			<div class="nowcastf-report-section-heading">
				<div><p class="nowcastf-report-eyebrow"><?php esc_html_e( 'Product performance', 'now-campaign-storefronts' ); ?></p><h2><?php esc_html_e( 'Product performance', 'now-campaign-storefronts' ); ?></h2></div>
			</div>
			<div class="nowcastf-report-product-list" data-report-products>
				<?php foreach ( $nowcastf_products as $nowcastf_product ) : ?>
					<div class="nowcastf-report-product-row">
						<div><strong><?php echo esc_html( (string) ( $nowcastf_product['name'] ?? '' ) ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) ( $nowcastf_product['net_units'] ?? 0 ) ) . ' ' . __( 'items', 'now-campaign-storefronts' ) ); ?></span></div>
						<strong><?php echo $nowcastf_product['net_sales_html'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</div>
				<?php endforeach; ?>
				<?php if ( ! $nowcastf_products ) : ?><p class="nowcastf-report-empty"><?php esc_html_e( 'There are no paid campaign product results yet.', 'now-campaign-storefronts' ); ?></p><?php endif; ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php if ( $authenticated ) : ?>
	<?php wp_print_scripts( [ 'nowcastf-report' ] ); ?>
<?php endif; ?>
</body>
</html>
