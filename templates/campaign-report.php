<?php
/** @var WP_Post $campaign */
/** @var WP_Post $reportPost */
/** @var int $campaignId */
/** @var string $key */
/** @var bool $authenticated */
/** @var array $presented */
/** @var string $styleUrl */
/** @var string $scriptUrl */
/** @var array $scriptConfig */

use WooCampaign\Campaign\Meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$start = absint( get_post_meta( $campaignId, Meta::START_AT, true ) );
$end = absint( get_post_meta( $campaignId, Meta::END_AT, true ) );
$summary = $authenticated ? (array) ( $presented['summary'] ?? [] ) : [];
$formatted = $authenticated ? (array) ( $presented['formatted'] ?? [] ) : [];
$products = $authenticated ? (array) ( $presented['products'] ?? [] ) : [];
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow,noarchive">
	<title><?php echo esc_html( $campaign->post_title . ' — Campaign Report' ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( $styleUrl ); ?>">
</head>
<body class="woo-campaign-report-body">
<main class="woo-campaign-report-shell">
	<?php if ( ! $authenticated ) : ?>
		<section class="woo-campaign-report-login" aria-labelledby="woo-campaign-report-login-title">
			<div class="woo-campaign-report-brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
			<p class="woo-campaign-report-eyebrow"><?php esc_html_e( 'Campaign Report', 'wc-campaign' ); ?></p>
			<h1 id="woo-campaign-report-login-title"><?php echo esc_html( $campaign->post_title ); ?></h1>
			<p class="woo-campaign-report-login-copy"><?php esc_html_e( 'Enter the password to view live campaign performance.', 'wc-campaign' ); ?></p>
			<form method="post" action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>">
				<label>
					<span><?php esc_html_e( 'Password', 'wc-campaign' ); ?></span>
					<input type="password" name="post_password" autocomplete="current-password" autofocus required>
				</label>
				<button type="submit"><?php esc_html_e( 'View report', 'wc-campaign' ); ?></button>
			</form>
		</section>
	<?php else : ?>
		<header class="woo-campaign-report-header">
			<div>
				<div class="woo-campaign-report-brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
				<p class="woo-campaign-report-eyebrow"><?php esc_html_e( 'Campaign Report', 'wc-campaign' ); ?></p>
				<h1><?php echo esc_html( $campaign->post_title ); ?></h1>
				<?php if ( $start || $end ) : ?>
					<p class="woo-campaign-report-period">
						<?php if ( $start ) : ?><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start ) ); ?><?php endif; ?>
						<?php if ( $start && $end ) : ?> <span aria-hidden="true">→</span> <?php endif; ?>
						<?php if ( $end ) : ?><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $end ) ); ?><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			<div class="woo-campaign-report-live"><span></span><?php esc_html_e( 'Live', 'wc-campaign' ); ?> · <span data-report-updated><?php echo esc_html( (string) ( $presented['updated_label'] ?? '' ) ); ?></span></div>
		</header>

		<section class="woo-campaign-report-hero-metric">
			<span><?php esc_html_e( 'Net Sales', 'wc-campaign' ); ?></span>
			<strong data-report-money="net_sales"><?php echo $formatted['net_sales'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
		</section>

		<section class="woo-campaign-report-metrics" aria-label="<?php esc_attr_e( 'Campaign summary', 'wc-campaign' ); ?>">
			<div><span><?php esc_html_e( 'Paid Orders', 'wc-campaign' ); ?></span><strong data-report-value="orders"><?php echo esc_html( number_format_i18n( (int) ( $summary['orders'] ?? 0 ) ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Units', 'wc-campaign' ); ?></span><strong data-report-value="units"><?php echo esc_html( number_format_i18n( (int) ( $summary['units'] ?? 0 ) ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Average Order', 'wc-campaign' ); ?></span><strong data-report-money="average_order"><?php echo $formatted['average_order'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Pending Orders', 'wc-campaign' ); ?></span><strong data-report-value="pending_orders"><?php echo esc_html( number_format_i18n( (int) ( $summary['pending_orders'] ?? 0 ) ) ); ?></strong></div>
		</section>

		<section class="woo-campaign-report-breakdown">
			<div><span><?php esc_html_e( 'Campaign Subtotal', 'wc-campaign' ); ?></span><strong data-report-money="campaign_subtotal"><?php echo $formatted['campaign_subtotal'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Discount', 'wc-campaign' ); ?></span><strong data-report-money="discount"><?php echo $formatted['discount'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Refund', 'wc-campaign' ); ?></span><strong data-report-money="refund"><?php echo $formatted['refund'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<div><span><?php esc_html_e( 'Refunded Units', 'wc-campaign' ); ?></span><strong data-report-value="refunded_units"><?php echo esc_html( number_format_i18n( (int) ( $summary['refunded_units'] ?? 0 ) ) ); ?></strong></div>
		</section>

		<section class="woo-campaign-report-products">
			<div class="woo-campaign-report-section-heading">
				<div><p class="woo-campaign-report-eyebrow"><?php esc_html_e( 'Product performance', 'wc-campaign' ); ?></p><h2><?php esc_html_e( 'Product performance', 'wc-campaign' ); ?></h2></div>
			</div>
			<div class="woo-campaign-report-product-list" data-report-products>
				<?php foreach ( $products as $product ) : ?>
					<div class="woo-campaign-report-product-row">
						<div><strong><?php echo esc_html( (string) ( $product['name'] ?? '' ) ); ?></strong><span><?php echo esc_html( number_format_i18n( (int) ( $product['net_units'] ?? 0 ) ) . ' ' . __( 'items', 'wc-campaign' ) ); ?></span></div>
						<strong><?php echo $product['net_sales_html'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</div>
				<?php endforeach; ?>
				<?php if ( ! $products ) : ?><p class="woo-campaign-report-empty"><?php esc_html_e( 'There are no paid campaign product results yet.', 'wc-campaign' ); ?></p><?php endif; ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php if ( $authenticated ) : ?>
	<script>window.WooCampaignLiveReport=<?php echo wp_json_encode( $scriptConfig ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;</script>
	<script src="<?php echo esc_url( $scriptUrl ); ?>" defer></script>
<?php endif; ?>
</body>
</html>
