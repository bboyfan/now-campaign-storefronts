<?php

use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$campaignId = (int) get_queried_object_id();
$campaign = get_post( $campaignId );
if ( ! $campaign instanceof WP_Post || PostType::TYPE !== $campaign->post_type ) {
	get_footer();
	return;
}

$mediaIds = get_post_meta( $campaignId, Meta::MEDIA_IDS, true );
$mediaIds = is_array( $mediaIds ) ? array_values( array_filter( array_map( 'absint', $mediaIds ) ) ) : [];
$mediaImages = [];
foreach ( $mediaIds as $mediaId ) {
	if ( ! wp_attachment_is_image( $mediaId ) ) {
		continue;
	}
	$image = wp_get_attachment_image( $mediaId, 'full', false, [ 'loading' => 'lazy', 'class' => 'woo-campaign-media-image' ] );
	if ( is_string( $image ) && '' !== trim( $image ) ) {
		$mediaImages[] = $image;
	}
}
if ( ! $mediaImages ) {
	$featuredId = get_post_thumbnail_id( $campaignId );
	if ( $featuredId > 0 && wp_attachment_is_image( $featuredId ) ) {
		$image = wp_get_attachment_image( $featuredId, 'full', false, [ 'loading' => 'lazy', 'class' => 'woo-campaign-media-image' ] );
		if ( is_string( $image ) && '' !== trim( $image ) ) {
			$mediaImages[] = $image;
		}
	}
}

$design = get_post_meta( $campaignId, Meta::DESIGN, true );
$design = Meta::sanitizeDesign( is_array( $design ) ? $design : [] );
$styleVars = [];
if ( ! empty( $design['content_width'] ) ) {
	$styleVars[] = '--woo-campaign-content-width:' . absint( $design['content_width'] ) . 'px';
}
if ( ! empty( $design['page_bg'] ) ) {
	$styleVars[] = '--woo-campaign-page-bg:' . $design['page_bg'];
}
if ( ! empty( $design['text_color'] ) ) {
	$styleVars[] = '--woo-campaign-text:' . $design['text_color'];
}
if ( ! empty( $design['accent_color'] ) ) {
	$styleVars[] = '--woo-campaign-accent:' . $design['accent_color'];
}
if ( ! empty( $design['surface_color'] ) ) {
	$styleVars[] = '--woo-campaign-surface:' . $design['surface_color'];
}
if ( ! empty( $design['border_color'] ) ) {
	$styleVars[] = '--woo-campaign-border:' . $design['border_color'];
}

?>
<main class="woo-campaign-page" data-woo-campaign-native-template style="<?php echo esc_attr( implode( ';', $styleVars ) ); ?>">
	<div class="woo-campaign-page-inner">
		<?php if ( $design['show_title'] ) : ?>
			<header class="woo-campaign-page-header">
				<h1><?php echo esc_html( wp_strip_all_tags( $campaign->post_title ) ); ?></h1>
			</header>
		<?php endif; ?>

		<?php if ( $mediaImages ) : ?>
			<div class="woo-campaign-media-gallery" data-woo-campaign-media-gallery>
				<?php foreach ( $mediaImages as $image ) : ?>
					<figure class="woo-campaign-media-item"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== trim( (string) $campaign->post_content ) ) : ?>
			<section class="woo-campaign-intro" data-woo-campaign-intro>
				<?php
				// Use the normal WordPress content pipeline so Campaign Intro supports
				// registered shortcodes and content integrations (e.g. reusable theme/plugin templates).
				// CampaignRenderer suppresses its commerce append while the native template is active.
				echo apply_filters( 'the_content', $campaign->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</section>
		<?php endif; ?>

		<div class="woo-campaign-page-sections">
			<?php echo do_shortcode( '[woo_campaign_products campaign_id="' . absint( $campaignId ) . '"]' ); ?>
		</div>
	</div>
</main>
<?php echo do_shortcode( '[woo_campaign_mini_cart]' ); ?>
<?php get_footer(); ?>