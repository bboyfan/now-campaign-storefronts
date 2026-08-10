<?php

namespace WooCampaign\Storefront;

use WooCampaign\CampaignProduct\CampaignProduct;
use WooCampaign\CampaignProduct\Repository as CampaignProductRepository;
use WooCampaign\CampaignSection\CampaignSection;
use WooCampaign\CampaignSection\Repository as CampaignSectionRepository;
use WooCampaign\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignSectionRenderer {
	public function __construct(
		private CampaignSectionRepository $sections,
		private CampaignProductRepository $campaignProducts,
		private ProductAdapter $products,
	) {}

	public function render( int $campaignId ): string {
		$products = $this->campaignProducts->forCampaign( $campaignId, true );
		$sections = $this->sections->forCampaign( $campaignId, true );
		if ( ! $products && ! $sections ) {
			return '<div class="woocommerce-info">' . esc_html__( 'No campaign products are available yet.', 'wc-campaign' ) . '</div>';
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
		<div class="woo-campaign-sections" data-campaign-id="<?php echo esc_attr( (string) $campaignId ); ?>">
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
		$sectionImage = $section->imageId > 0 ? $this->attachmentImage( $section->imageId, 'large', 'woo-campaign-section-image' ) : '';
		?>
		<section class="woo-campaign-section layout-<?php echo esc_attr( $layout ); ?>" data-campaign-section="<?php echo esc_attr( (string) $section->id ); ?>" data-campaign-layout="<?php echo esc_attr( $layout ); ?>"<?php echo '' !== $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
			<?php if ( '' !== $sectionImage ) : ?><div class="woo-campaign-section-media"><?php echo $sectionImage; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<?php if ( '' !== $section->title || '' !== $section->description ) : ?>
				<header class="woo-campaign-section-heading">
					<?php if ( '' !== $section->title ) : ?><h2><?php echo esc_html( $section->title ); ?></h2><?php endif; ?>
					<?php if ( '' !== $section->description ) : ?><div class="woo-campaign-section-description"><?php echo wp_kses_post( wpautop( $section->description ) ); ?></div><?php endif; ?>
				</header>
			<?php endif; ?>
			<?php if ( $rows ) : ?>
				<?php if ( CampaignSection::LAYOUT_QUICK_ORDER === $layout ) : ?>
					<div class="woo-campaign-purchase-list">
						<?php foreach ( $rows as $row ) : $this->renderQuickOrderRow( $campaignId, $row ); endforeach; ?>
					</div>
				<?php elseif ( CampaignSection::LAYOUT_EDITORIAL === $layout ) : ?>
					<div class="woo-campaign-editorial-list">
						<?php foreach ( $rows as $row ) : $this->renderEditorialItem( $campaignId, $row ); endforeach; ?>
					</div>
				<?php else : ?>
					<div class="woo-campaign-compact-grid">
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
		$image = $context['image_id'] > 0 ? $this->attachmentImage( $context['image_id'], 'woocommerce_thumbnail', 'woo-campaign-purchase-row-image' ) : '';
		?>
		<article class="woo-campaign-purchase-row<?php echo '' === $image ? ' has-no-image' : ''; ?><?php echo $context['available'] ? '' : ' is-unavailable'; ?>" data-campaign-product-option data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>" data-campaign-price="<?php echo esc_attr( (string) $context['campaign'] ); ?>">
			<?php if ( '' !== $image ) : ?><div class="woo-campaign-purchase-row-media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="woo-campaign-purchase-row-content">
				<div class="woo-campaign-purchase-row-identity">
					<h3><?php echo esc_html( $context['display_title'] ); ?></h3>
					<?php if ( '' !== $context['copy'] ) : ?><div class="woo-campaign-row-copy"><?php echo esc_html( $context['copy'] ); ?></div><?php endif; ?>
					<?php if ( '' !== $context['stock_note'] ) : ?><div class="woo-campaign-stock-note"><?php echo esc_html( $context['stock_note'] ); ?></div><?php endif; ?>
				</div>
				<?php $this->renderPrice( $context['base'], $context['campaign'], 'woo-campaign-purchase-row-price' ); ?>
			</div>
			<div class="woo-campaign-purchase-row-actions">
				<?php if ( $context['available'] ) : ?>
					<div class="woo-campaign-purchase-row-quantity">
						<span><?php esc_html_e( 'Quantity', 'wc-campaign' ); ?></span>
						<div class="woo-campaign-quantity">
							<button type="button" data-woo-campaign-product-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'wc-campaign' ); ?>">−</button>
							<input type="number" min="1" step="1" value="1" inputmode="numeric" data-woo-campaign-qty aria-label="<?php esc_attr_e( 'Quantity', 'wc-campaign' ); ?>">
							<button type="button" data-woo-campaign-product-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'wc-campaign' ); ?>">+</button>
						</div>
					</div>
					<button type="button" class="button alt woo-campaign-add-to-cart" data-campaign-id="<?php echo esc_attr( (string) $campaignId ); ?>" data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>">
						<span class="woo-campaign-button-label"><?php esc_html_e( 'Add to cart', 'wc-campaign' ); ?></span>
						<span class="woo-campaign-button-spinner" aria-hidden="true"></span>
					</button>
				<?php else : ?>
					<span class="woo-campaign-sold-out"><?php esc_html_e( 'Sold out', 'wc-campaign' ); ?></span>
				<?php endif; ?>
				<div class="woo-campaign-product-feedback" role="status" aria-live="polite" data-woo-campaign-product-feedback></div>
			</div>
		</article>
		<?php
	}

	private function renderEditorialItem( int $campaignId, CampaignProduct $row ): void {
		$context = $this->productContext( $row );
		if ( null === $context ) {
			return;
		}
		$image = $context['image_id'] > 0 ? $this->attachmentImage( $context['image_id'], 'woocommerce_single', 'woo-campaign-editorial-image' ) : '';
		?>
		<article class="woo-campaign-editorial-item<?php echo '' === $image ? ' has-no-image' : ''; ?><?php echo $context['available'] ? '' : ' is-unavailable'; ?>" data-campaign-product-option data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>" data-campaign-price="<?php echo esc_attr( (string) $context['campaign'] ); ?>">
			<?php if ( '' !== $image ) : ?><div class="woo-campaign-editorial-media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="woo-campaign-editorial-content">
				<div class="woo-campaign-editorial-copy">
					<h3><?php echo esc_html( $context['display_title'] ); ?></h3>
					<?php if ( '' !== $context['copy'] ) : ?><div class="woo-campaign-editorial-description"><?php echo wp_kses_post( wpautop( $row->campaignCopy ) ); ?></div><?php endif; ?>
					<?php if ( '' !== $context['stock_note'] ) : ?><div class="woo-campaign-stock-note"><?php echo esc_html( $context['stock_note'] ); ?></div><?php endif; ?>
				</div>
				<?php $this->renderPrice( $context['base'], $context['campaign'], 'woo-campaign-editorial-price' ); ?>
				<div class="woo-campaign-editorial-actions"><?php $this->renderPurchaseActions( $campaignId, $row->id, $context['available'], true ); ?></div>
			</div>
		</article>
		<?php
	}

	private function renderCompactItem( int $campaignId, CampaignProduct $row ): void {
		$context = $this->productContext( $row );
		if ( null === $context ) {
			return;
		}
		$image = $context['image_id'] > 0 ? $this->attachmentImage( $context['image_id'], 'woocommerce_thumbnail', 'woo-campaign-compact-image' ) : '';
		?>
		<article class="woo-campaign-compact-card<?php echo '' === $image ? ' has-no-image' : ''; ?><?php echo $context['available'] ? '' : ' is-unavailable'; ?>" data-campaign-product-option data-campaign-product-id="<?php echo esc_attr( (string) $row->id ); ?>" data-campaign-price="<?php echo esc_attr( (string) $context['campaign'] ); ?>">
			<?php if ( '' !== $image ) : ?><div class="woo-campaign-compact-card-media"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
			<div class="woo-campaign-compact-card-content">
				<h3><?php echo esc_html( $context['display_title'] ); ?></h3>
				<?php if ( '' !== $context['copy'] ) : ?><p class="woo-campaign-compact-copy"><?php echo esc_html( $context['copy'] ); ?></p><?php endif; ?>
				<?php if ( '' !== $context['stock_note'] ) : ?><div class="woo-campaign-stock-note"><?php echo esc_html( $context['stock_note'] ); ?></div><?php endif; ?>
				<?php $this->renderPrice( $context['base'], $context['campaign'], 'woo-campaign-compact-price' ); ?>
				<div class="woo-campaign-compact-actions"><?php $this->renderPurchaseActions( $campaignId, $row->id, $context['available'], true ); ?></div>
			</div>
		</article>
		<?php
	}

	private function renderPurchaseActions( int $campaignId, int $campaignProductId, bool $available, bool $compact = false ): void {
		if ( ! $available ) {
			echo '<span class="woo-campaign-sold-out">' . esc_html__( 'Sold out', 'wc-campaign' ) . '</span>';
			return;
		}
		?>
		<div class="woo-campaign-purchase-controls<?php echo $compact ? ' is-compact' : ''; ?>">
			<div class="woo-campaign-quantity">
				<button type="button" data-woo-campaign-product-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'wc-campaign' ); ?>">−</button>
				<input type="number" min="1" step="1" value="1" inputmode="numeric" data-woo-campaign-qty aria-label="<?php esc_attr_e( 'Quantity', 'wc-campaign' ); ?>">
				<button type="button" data-woo-campaign-product-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'wc-campaign' ); ?>">+</button>
			</div>
			<button type="button" class="button alt woo-campaign-add-to-cart" data-campaign-id="<?php echo esc_attr( (string) $campaignId ); ?>" data-campaign-product-id="<?php echo esc_attr( (string) $campaignProductId ); ?>">
				<span class="woo-campaign-button-label"><?php esc_html_e( 'Add to cart', 'wc-campaign' ); ?></span>
				<span class="woo-campaign-button-spinner" aria-hidden="true"></span>
			</button>
		</div>
		<div class="woo-campaign-product-feedback" role="status" aria-live="polite" data-woo-campaign-product-feedback></div>
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
		$product = $this->products->get( $row->saleableId() );
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}
		$parent = $row->variationId > 0 ? $this->products->get( $row->productId ) : $product;
		$displayProduct = $parent instanceof \WC_Product ? $parent : $product;
		$imageId = $product->get_image_id() ?: $displayProduct->get_image_id();
		$available = $product->is_purchasable() && $product->is_in_stock();
		$title = $this->normalizeName( $displayProduct->get_name() );
		$variation = $product instanceof \WC_Product_Variation ? $this->variationLabel( $product ) : '';
		$displayTitle = '' !== $variation ? $title . ' — ' . $variation : $title;

		return [
			'product'       => $product,
			'title'         => $title,
			'display_title' => $displayTitle,
			'variation'     => $variation,
			'copy'          => trim( wp_strip_all_tags( (string) $row->campaignCopy ) ),
			'image_id'      => (int) $imageId,
			'base'          => (float) $product->get_price( 'edit' ),
			'campaign'      => (float) $row->campaignPrice,
			'available'     => $available,
			'stock_note'    => $available ? $this->stockNote( $product ) : '',
		];
	}

	private function stockNote( \WC_Product $product ): string {
		if ( ! $product->managing_stock() ) {
			return '';
		}
		$quantity = $product->get_stock_quantity();
		if ( null === $quantity || $quantity <= 0 ) {
			return '';
		}
		$threshold = function_exists( 'wc_get_low_stock_amount' ) ? (int) wc_get_low_stock_amount( $product ) : 2;
		if ( $threshold > 0 && $quantity <= $threshold ) {
			return sprintf( /* translators: %d: remaining stock quantity */ __( 'Only %d left', 'wc-campaign' ), $quantity );
		}
		return '';
	}

	private function variationLabel( \WC_Product_Variation $variation ): string {
		$label = wc_get_formatted_variation( $variation, true, false, true );
		return $this->normalizeName( is_string( $label ) ? $label : '' );
	}

	private function attachmentImage( int $imageId, string $size, string $class ): string {
		if ( $imageId <= 0 || ! wp_attachment_is_image( $imageId ) ) {
			return '';
		}
		$image = wp_get_attachment_image( $imageId, $size, false, [ 'loading' => 'lazy', 'class' => $class ] );
		return is_string( $image ) ? trim( $image ) : '';
	}

	private function normalizeName( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/\s+/u', ' ', $value );
		return trim( is_string( $value ) ? $value : '' );
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