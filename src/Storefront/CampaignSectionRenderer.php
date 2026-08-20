<?php

namespace Bboyfan\NowCampaignStorefronts\Storefront;

use Bboyfan\NowCampaignStorefronts\CampaignProduct\CampaignProduct;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\CampaignProductPresentationResolver;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\Repository as CampaignProductRepository;
use Bboyfan\NowCampaignStorefronts\CampaignSection\CampaignSection;
use Bboyfan\NowCampaignStorefronts\CampaignSection\Repository as CampaignSectionRepository;
use Bboyfan\NowCampaignStorefronts\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignSectionRenderer {
	public function __construct(
		private CampaignSectionRepository $sections,
		private CampaignProductRepository $campaignProducts,
		private ProductAdapter $products,
		private CampaignProductPresentationResolver $presentation,
	) {}

	public function render( int $campaignId ): string {
		$products = $this->campaignProducts->forCampaign( $campaignId, true );
		$sections = $this->sections->forCampaign( $campaignId, true );
		if ( ! $products && ! $sections ) {
			return '<div class="woocommerce-info">' . esc_html__( 'No campaign products are available yet.', 'now-campaign-storefronts' ) . '</div>';
		}
		if ( ! $sections ) {
			$sections = [ new CampaignSection( 0, $campaignId, '', '', 0, CampaignSection::LAYOUT_QUICK_ORDER, 'active', 0 ) ];
		}

		$bySection = [];
		$knownSectionIds = [];
		foreach ( $sections as $section ) {
			$knownSectionIds[ $section->id ] = true;
			$bySection[ $section->id ] = [];
		}
		$firstSectionId = $sections[0]->id;
		foreach ( $products as $product ) {
			$sectionId = isset( $knownSectionIds[ $product->sectionId ] ) ? $product->sectionId : $firstSectionId;
			$bySection[ $sectionId ][] = $product;
		}

		ob_start();
		?>
		<div class="nowcastf-sections" data-campaign-id="<?php echo esc_attr( (string) $campaignId ); ?>">
			<?php foreach ( $sections as $section ) : ?>
				<?php $this->renderSection( $campaignId, $section, $bySection[ $section->id ] ?? [] ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function renderSection( int $campaignId, CampaignSection $section, array $rows ): void {
		$layout = in_array( $section->layout, CampaignSection::layouts(), true ) ? $section->layout : CampaignSection::LAYOUT_QUICK_ORDER;
		$style = $this->sectionStyle( $section );
		$sectionImage = $section->imageId > 0 ? $this->attachmentImage( $section->imageId, 'large', 'nowcastf-section-image' ) : '';
		?>
		<section class="nowcastf-section layout-<?php echo esc_attr( $layout ); ?>" data-campaign-section="<?php echo esc_attr( (string) $section->id ); ?>" data-campaign-layout="<?php echo esc_attr( $layout ); ?>"<?php echo '' !== $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
			<?php if ( '' !== $sectionImage ) : ?><div class="nowcastf-section-media"><?php echo $sectionImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<?php if ( '' !== $section->title || '' !== $section->description ) : ?>
				<header class="nowcastf-section-heading">
					<?php if ( '' !== $section->title ) : ?><h2><?php echo esc_html( $section->title ); ?></h2><?php endif; ?>
					<?php if ( '' !== $section->description ) : ?><div class="nowcastf-section-description"><?php echo wp_kses_post( wpautop( $section->description ) ); ?></div><?php endif; ?>
				</header>
			<?php endif; ?>
			<?php if ( $rows ) : ?>
				<?php if ( CampaignSection::LAYOUT_QUICK_ORDER === $layout ) : ?>
					<div class="nowcastf-purchase-list">
						<?php foreach ( $rows as $row ) : $this->renderQuickOrderRow( $campaignId, $row ); endforeach; ?>
					</div>
				<?php elseif ( CampaignSection::LAYOUT_EDITORIAL === $layout ) : ?>
					<div class="nowcastf-editorial-list">
						<?php foreach ( $rows as $row ) : $this->renderEditorialItem( $campaignId, $row ); endforeach; ?>
					</div>
				<?php else : ?>
					<div class="nowcastf-compact-grid">
						<?php foreach ( $rows as $row ) : $this->renderCompactItem( $campaignId, $row ); endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</section>
		<?php
	}

	private function renderQuickOrderRow( int $campaignId, CampaignProduct $row ): void {
		$context = $this->productContext( $row );
		if ( null === $context ) {
			return;
		}
		$image = $context['image_id'] > 0 ? $this->attachmentImage( $context['image_id'], 'woocommerce_thumbnail', 'nowcastf-purchase-row-image' ) : '';
		?>
		<article class="nowcastf-purchase-row<?php echo '' === $image ? ' has-no-image' : ''; ?><?php echo $context['available'] ? '' : ' is-unavailable'; ?>" data-campaign-product-option data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>" data-campaign-price="<?php echo esc_attr( (string) $context['campaign'] ); ?>">
			<?php if ( '' !== $image ) : ?><div class="nowcastf-purchase-row-media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="nowcastf-purchase-row-content">
				<div class="nowcastf-purchase-row-identity">
					<h3><?php echo esc_html( $context['display_title'] ); ?></h3>
					<?php if ( '' !== $context['copy'] ) : ?><div class="nowcastf-row-copy"><?php echo esc_html( $context['copy'] ); ?></div><?php endif; ?>
					<?php if ( '' !== $context['stock_note'] ) : ?><div class="nowcastf-stock-note"><?php echo esc_html( $context['stock_note'] ); ?></div><?php endif; ?>
				</div>
				<?php $this->renderPrice( $context['base'], $context['campaign'], 'nowcastf-purchase-row-price' ); ?>
			</div>
			<div class="nowcastf-purchase-row-actions">
				<?php if ( $context['available'] ) : ?>
					<div class="nowcastf-purchase-row-quantity">
						<span><?php esc_html_e( 'Quantity', 'now-campaign-storefronts' ); ?></span>
						<div class="nowcastf-quantity">
							<button type="button" data-woo-campaign-product-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'now-campaign-storefronts' ); ?>">−</button>
							<input type="number" min="1" step="1" value="1" inputmode="numeric" data-woo-campaign-qty aria-label="<?php esc_attr_e( 'Quantity', 'now-campaign-storefronts' ); ?>">
							<button type="button" data-woo-campaign-product-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'now-campaign-storefronts' ); ?>">+</button>
						</div>
					</div>
					<button type="button" class="button alt woo-campaign-add-to-cart" data-campaign-id="<?php echo esc_attr( (string) $campaignId ); ?>" data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>">
						<span class="nowcastf-button-label"><?php esc_html_e( 'Add to cart', 'now-campaign-storefronts' ); ?></span>
						<span class="nowcastf-button-spinner" aria-hidden="true"></span>
					</button>
				<?php else : ?>
					<span class="nowcastf-sold-out"><?php esc_html_e( 'Sold out', 'now-campaign-storefronts' ); ?></span>
				<?php endif; ?>
				<div class="nowcastf-product-feedback" role="status" aria-live="polite" data-woo-campaign-product-feedback></div>
			</div>
		</article>
		<?php
	}

	private function renderEditorialItem( int $campaignId, CampaignProduct $row ): void {
		$context = $this->productContext( $row );
		if ( null === $context ) {
			return;
		}
		$image = $context['image_id'] > 0 ? $this->attachmentImage( $context['image_id'], 'woocommerce_single', 'nowcastf-editorial-image' ) : '';
		?>
		<article class="nowcastf-editorial-item<?php echo '' === $image ? ' has-no-image' : ''; ?><?php echo $context['available'] ? '' : ' is-unavailable'; ?>" data-campaign-product-option data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>" data-campaign-price="<?php echo esc_attr( (string) $context['campaign'] ); ?>">
			<?php if ( '' !== $image ) : ?><div class="nowcastf-editorial-media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="nowcastf-editorial-content">
				<div class="nowcastf-editorial-copy">
					<h3><?php echo esc_html( $context['display_title'] ); ?></h3>
					<?php if ( '' !== $context['copy'] ) : ?><div class="nowcastf-editorial-description"><?php echo wp_kses_post( wpautop( $row->campaignCopy ) ); ?></div><?php endif; ?>
					<?php if ( '' !== $context['stock_note'] ) : ?><div class="nowcastf-stock-note"><?php echo esc_html( $context['stock_note'] ); ?></div><?php endif; ?>
				</div>
				<?php $this->renderPrice( $context['base'], $context['campaign'], 'nowcastf-editorial-price' ); ?>
				<div class="nowcastf-editorial-actions"><?php $this->renderPurchaseActions( $campaignId, $row->id, $context['available'], true ); ?></div>
			</div>
		</article>
		<?php
	}

	private function renderCompactItem( int $campaignId, CampaignProduct $row ): void {
		$context = $this->productContext( $row );
		if ( null === $context ) {
			return;
		}
		$image = $context['image_id'] > 0 ? $this->attachmentImage( $context['image_id'], 'woocommerce_thumbnail', 'nowcastf-compact-image' ) : '';
		?>
		<article class="nowcastf-compact-card<?php echo '' === $image ? ' has-no-image' : ''; ?><?php echo $context['available'] ? '' : ' is-unavailable'; ?>" data-campaign-product-option data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>" data-campaign-price="<?php echo esc_attr( (string) $context['campaign'] ); ?>">
			<?php if ( '' !== $image ) : ?><div class="nowcastf-compact-card-media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="nowcastf-compact-card-content">
				<h3><?php echo esc_html( $context['display_title'] ); ?></h3>
				<?php if ( '' !== $context['copy'] ) : ?><p class="nowcastf-compact-copy"><?php echo esc_html( $context['copy'] ); ?></p><?php endif; ?>
				<?php if ( '' !== $context['stock_note'] ) : ?><div class="nowcastf-stock-note"><?php echo esc_html( $context['stock_note'] ); ?></div><?php endif; ?>
				<?php $this->renderPrice( $context['base'], $context['campaign'], 'nowcastf-compact-price' ); ?>
				<div class="nowcastf-compact-actions"><?php $this->renderPurchaseActions( $campaignId, $row->id, $context['available'], true ); ?></div>
			</div>
		</article>
		<?php
	}

	private function renderPurchaseActions( int $campaignId, int $campaignProductId, bool $available, bool $compact = false ): void {
		if ( ! $available ) {
			echo '<span class="nowcastf-sold-out">' . esc_html__( 'Sold out', 'now-campaign-storefronts' ) . '</span>';
			return;
		}
		?>
		<div class="nowcastf-purchase-controls<?php echo $compact ? ' is-compact' : ''; ?>">
			<div class="nowcastf-quantity">
				<button type="button" data-woo-campaign-product-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'now-campaign-storefronts' ); ?>">−</button>
				<input type="number" min="1" step="1" value="1" inputmode="numeric" data-woo-campaign-qty aria-label="<?php esc_attr_e( 'Quantity', 'now-campaign-storefronts' ); ?>">
				<button type="button" data-woo-campaign-product-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'now-campaign-storefronts' ); ?>">+</button>
			</div>
			<button type="button" class="button alt woo-campaign-add-to-cart" data-campaign-id="<?php echo esc_attr( (string) $campaignId ); ?>" data-campaign-product-id="<?php echo esc_attr( (string) $campaignProductId ); ?>">
				<span class="nowcastf-button-label"><?php esc_html_e( 'Add to cart', 'now-campaign-storefronts' ); ?></span>
				<span class="nowcastf-button-spinner" aria-hidden="true"></span>
			</button>
		</div>
		<div class="nowcastf-product-feedback" role="status" aria-live="polite" data-woo-campaign-product-feedback></div>
		<?php
	}

	private function renderPrice( float $base, float $campaign, string $class ): void {
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<?php if ( $base > 0 && $campaign < $base ) : ?><del><?php echo wp_kses_post( wc_price( $base ) ); ?></del><?php endif; ?>
			<strong><?php echo wp_kses_post( wc_price( $campaign ) ); ?></strong>
		</div>
		<?php
	}

	private function productContext( CampaignProduct $row ): ?array {
		return $this->presentation->resolve( $row );
	}

	private function attachmentImage( int $imageId, string $size, string $class ): string {
		if ( $imageId <= 0 || ! wp_attachment_is_image( $imageId ) ) {
			return '';
		}
		$image = wp_get_attachment_image( $imageId, $size, false, [ 'loading' => 'lazy', 'class' => $class ] );
		return is_string( $image ) ? trim( $image ) : '';
	}

	private function sectionStyle( CampaignSection $section ): string {
		$vars = [];
		if ( '' !== $section->titleColor ) {
			$vars[] = '--woo-campaign-section-title-color:' . $section->titleColor;
		}
		if ( '' !== $section->copyColor ) {
			$vars[] = '--woo-campaign-section-copy-color:' . $section->copyColor;
		}
		if ( '' !== $section->ctaBgColor ) {
			$vars[] = '--woo-campaign-section-cta-bg:' . $section->ctaBgColor;
		}
		if ( '' !== $section->ctaTextColor ) {
			$vars[] = '--woo-campaign-section-cta-text:' . $section->ctaTextColor;
		}
		return implode( ';', $vars );
	}
}