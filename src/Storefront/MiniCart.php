<?php

namespace WooCampaign\Storefront;

use WooCampaign\Cart\CartService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MiniCart {
	public function __construct(
		private CartService $cart,
		private Assets $assets,
	) {}

	public function render(): string {
		$this->assets->enqueue();
		$snapshot = $this->cart->snapshot();
		ob_start();
		?>
		<div class="woo-campaign-mini-cart<?php echo $snapshot['count'] > 0 ? ' has-items' : ' is-empty'; ?>" data-woo-campaign-mini-cart>
			<button type="button" class="woo-campaign-mini-cart-bar" data-woo-campaign-mini-toggle aria-expanded="false">
				<span class="woo-campaign-mini-cart-bar-main">
					<span class="woo-campaign-mini-cart-icon" aria-hidden="true"><span class="dashicons dashicons-cart"></span></span>
					<span class="woo-campaign-mini-cart-copy">
						<strong><?php esc_html_e( 'Your cart', 'wc-campaign' ); ?></strong>
						<small><span data-woo-campaign-cart-count><?php echo esc_html( (string) $snapshot['count'] ); ?></span> <?php esc_html_e( 'items', 'wc-campaign' ); ?></small>
					</span>
				</span>
				<span class="woo-campaign-mini-cart-bar-total">
					<strong data-woo-campaign-cart-total><?php echo wp_kses_post( $snapshot['total'] ); ?></strong>
					<span class="dashicons dashicons-arrow-up-alt2" data-woo-campaign-cart-chevron aria-hidden="true"></span>
				</span>
			</button>
			<div class="woo-campaign-mini-cart-panel" data-woo-campaign-mini-panel hidden>
				<div class="woo-campaign-mini-cart-header">
					<div>
						<span class="woo-campaign-section-eyebrow"><?php esc_html_e( 'Cart summary', 'wc-campaign' ); ?></span>
						<h3><?php esc_html_e( 'Ready when you are', 'wc-campaign' ); ?></h3>
					</div>
					<button type="button" class="woo-campaign-mini-cart-close" data-woo-campaign-mini-close aria-label="<?php esc_attr_e( 'Close cart', 'wc-campaign' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<div class="woo-campaign-mini-cart-items" data-woo-campaign-cart-items>
					<?php echo $this->renderItems( $snapshot['items'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="woo-campaign-mini-cart-footer">
					<div class="woo-campaign-mini-cart-summary">
						<div><span><?php esc_html_e( 'Subtotal', 'wc-campaign' ); ?></span><span data-woo-campaign-cart-subtotal><?php echo wp_kses_post( $snapshot['subtotal'] ); ?></span></div>
						<div class="woo-campaign-mini-cart-discount-row"><span><?php esc_html_e( 'Discount', 'wc-campaign' ); ?></span><span data-woo-campaign-cart-discount><?php echo wp_kses_post( $snapshot['discount'] ); ?></span></div>
						<div class="woo-campaign-mini-cart-total"><span><?php esc_html_e( 'Total', 'wc-campaign' ); ?></span><strong data-woo-campaign-cart-total><?php echo wp_kses_post( $snapshot['total'] ); ?></strong></div>
					</div>
					<div class="woo-campaign-mini-cart-actions">
						<a class="button alt woo-campaign-checkout" href="<?php echo esc_url( $snapshot['checkoutUrl'] ); ?>"><?php esc_html_e( 'Proceed to checkout', 'wc-campaign' ); ?><span aria-hidden="true">→</span></a>
					</div>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function renderItems( array $items ): string {
		if ( ! $items ) {
			return '<div class="woo-campaign-cart-empty"><span class="dashicons dashicons-cart"></span><strong>' . esc_html__( 'Your cart is empty', 'wc-campaign' ) . '</strong><p>' . esc_html__( 'Add a campaign item to get started.', 'wc-campaign' ) . '</p></div>';
		}
		$html = '';
		foreach ( $items as $item ) {
			$html .= '<div class="woo-campaign-mini-cart-item' . ( $item['campaign'] ? ' is-campaign' : '' ) . '" data-cart-item-key="' . esc_attr( $item['key'] ) . '">';
			$html .= '<div class="woo-campaign-mini-cart-item-image">';
			if ( $item['image'] ) {
				$html .= '<img src="' . esc_url( $item['image'] ) . '" alt="">';
			} else {
				$html .= '<span class="woo-campaign-mini-cart-image-placeholder" aria-hidden="true"></span>';
			}
			$html .= '</div>';
			$html .= '<div class="woo-campaign-mini-cart-item-main"><div class="woo-campaign-mini-cart-item-title"><strong>' . esc_html( $item['name'] ) . '</strong>';
			if ( $item['campaign'] ) {
				$html .= '<span class="woo-campaign-badge">' . esc_html__( 'Campaign', 'wc-campaign' ) . '</span>';
			}
			$html .= '</div>';
			$html .= '<div class="woo-campaign-mini-cart-item-controls">';
			$html .= '<div class="woo-campaign-cart-quantity"><button type="button" data-woo-campaign-cart-step="-1" aria-label="' . esc_attr__( 'Decrease quantity', 'wc-campaign' ) . '">−</button><input type="number" min="0" step="1" value="' . esc_attr( (string) $item['quantity'] ) . '" data-woo-campaign-cart-qty aria-label="' . esc_attr__( 'Quantity', 'wc-campaign' ) . '"><button type="button" data-woo-campaign-cart-step="1" aria-label="' . esc_attr__( 'Increase quantity', 'wc-campaign' ) . '">+</button></div>';
			$html .= '<button type="button" class="woo-campaign-remove-link" data-woo-campaign-cart-remove>' . esc_html__( 'Remove', 'wc-campaign' ) . '</button></div></div>';
			$html .= '<div class="woo-campaign-mini-cart-item-total">' . wp_kses_post( $item['lineTotal'] ) . '</div></div>';
		}
		return $html;
	}
}
