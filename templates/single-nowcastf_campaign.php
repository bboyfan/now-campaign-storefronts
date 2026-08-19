<?php

use NowCampaignStorefronts\Campaign\Meta;
use NowCampaignStorefronts\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$nowcastf_campaign_id = (int) get_queried_object_id();
$nowcastf_campaign = get_post( $nowcastf_campaign_id );
if ( ! $nowcastf_campaign instanceof WP_Post || PostType::TYPE !== $nowcastf_campaign->post_type ) {
	get_footer();
	return;
}

$nowcastf_media_ids = get_post_meta( $nowcastf_campaign_id, Meta::MEDIA_IDS, true );
$nowcastf_media_ids = is_array( $nowcastf_media_ids ) ? array_values( array_filter( array_map( 'absint', $nowcastf_media_ids ) ) ) : [];
$nowcastf_media_images = [];
foreach ( $nowcastf_media_ids as $nowcastf_media_id ) {
	if ( ! wp_attachment_is_image( $nowcastf_media_id ) ) {
		continue;
	}
	$nowcastf_image = wp_get_attachment_image( $nowcastf_media_id, 'full', false, [ 'loading' => 'lazy', 'class' => 'nowcastf-media-image' ] );
	if ( is_string( $nowcastf_image ) && '' !== trim( $nowcastf_image ) ) {
		$nowcastf_media_images[] = $nowcastf_image;
	}
}
if ( ! $nowcastf_media_images ) {
	$nowcastf_featured_id = get_post_thumbnail_id( $nowcastf_campaign_id );
	if ( $nowcastf_featured_id > 0 && wp_attachment_is_image( $nowcastf_featured_id ) ) {
		$nowcastf_image = wp_get_attachment_image( $nowcastf_featured_id, 'full', false, [ 'loading' => 'lazy', 'class' => 'nowcastf-media-image' ] );
		if ( is_string( $nowcastf_image ) && '' !== trim( $nowcastf_image ) ) {
			$nowcastf_media_images[] = $nowcastf_image;
		}
	}
}

$nowcastf_design = get_post_meta( $nowcastf_campaign_id, Meta::DESIGN, true );
$nowcastf_design = Meta::sanitizeDesign( is_array( $nowcastf_design ) ? $nowcastf_design : [] );
$nowcastf_style_vars = [];
if ( ! empty( $nowcastf_design['content_width'] ) ) {
	$nowcastf_style_vars[] = '--woo-campaign-content-width:' . absint( $nowcastf_design['content_width'] ) . 'px';
}
if ( ! empty( $nowcastf_design['page_bg'] ) ) {
	$nowcastf_style_vars[] = '--woo-campaign-page-bg:' . $nowcastf_design['page_bg'];
}
if ( ! empty( $nowcastf_design['text_color'] ) ) {
	$nowcastf_style_vars[] = '--woo-campaign-text:' . $nowcastf_design['text_color'];
}
if ( ! empty( $nowcastf_design['accent_color'] ) ) {
	$nowcastf_style_vars[] = '--woo-campaign-accent:' . $nowcastf_design['accent_color'];
}
if ( ! empty( $nowcastf_design['surface_color'] ) ) {
	$nowcastf_style_vars[] = '--woo-campaign-surface:' . $nowcastf_design['surface_color'];
}
if ( ! empty( $nowcastf_design['border_color'] ) ) {
	$nowcastf_style_vars[] = '--woo-campaign-border:' . $nowcastf_design['border_color'];
}

?>
<main class="nowcastf-page" data-woo-campaign-native-template style="<?php echo esc_attr( implode( ';', $nowcastf_style_vars ) ); ?>">
	<div class="nowcastf-page-inner">
		<?php if ( $nowcastf_design['show_title'] ) : ?>
			<header class="nowcastf-page-header">
				<h1><?php echo esc_html( wp_strip_all_tags( $nowcastf_campaign->post_title ) ); ?></h1>
			</header>
		<?php endif; ?>

		<?php if ( $nowcastf_media_images ) : ?>
			<div class="nowcastf-media-gallery" data-woo-campaign-media-gallery>
				<?php foreach ( $nowcastf_media_images as $nowcastf_img ) : ?>
					<figure class="nowcastf-media-item"><?php echo $nowcastf_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== trim( (string) $nowcastf_campaign->post_content ) ) : ?>
			<section class="nowcastf-intro" data-woo-campaign-intro>
				<?php
				// Use the normal WordPress content pipeline so Campaign Intro supports
				// registered shortcodes and content integrations (e.g. reusable theme/plugin templates).
				// CampaignRenderer suppresses its commerce append while the native template is active.
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				echo apply_filters( 'the_content', $nowcastf_campaign->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</section>
		<?php endif; ?>

		<div class="nowcastf-page-sections">
			<?php echo do_shortcode( '[nowcastf_products campaign_id="' . absint( $nowcastf_campaign_id ) . '"]' ); ?>
		</div>
	</div>
</main>
<?php echo do_shortcode( '[nowcastf_mini_cart]' ); ?>
<?php get_footer(); ?>