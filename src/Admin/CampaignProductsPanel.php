<?php

namespace Bboyfan\NowCampaignStorefronts\Admin;

use Bboyfan\NowCampaignStorefronts\Campaign\PostType;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\Repository;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\Service;
use Bboyfan\NowCampaignStorefronts\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignProductsPanel {
	private const NONCE_ACTION = 'nowcastf_save_products';
	private const NONCE_NAME = 'nowcastf_products_nonce';

	public function __construct(
		private Repository $repository,
		private Service $service,
		private ProductAdapter $products,
	) {}

	public function register(): void {
		add_action( 'add_meta_boxes_' . PostType::TYPE, [ $this, 'addMetaBox' ] );
		add_action( 'save_post_' . PostType::TYPE, [ $this, 'save' ], 20, 2 );
		add_action( 'wp_ajax_nowcastf_get_product_variations', [ $this, 'ajaxGetProductVariations' ] );
	}

	public function addMetaBox(): void {
		add_meta_box( 'nowcastf-products', __( 'Campaign Products', 'now-campaign-storefronts' ), [ $this, 'render' ], PostType::TYPE, 'normal', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$rows = $this->repository->forCampaign( $post->ID );
		?>
		<div class="nowcastf-products-panel" data-next-order="<?php echo esc_attr( (string) count( $rows ) ); ?>">
			<div class="nowcastf-products-toolbar">
				<div>
					<div class="nowcastf-products-title-row">
						<strong><?php esc_html_e( 'Products in this campaign', 'now-campaign-storefronts' ); ?></strong>
						<span class="nowcastf-count-badge" data-woo-campaign-product-count><?php echo esc_html( (string) count( $rows ) ); ?></span>
					</div>
					<p><?php esc_html_e( 'Choose simple products or a variable product. Variable products open a picker so each variation can have its own Campaign Price.', 'now-campaign-storefronts' ); ?></p>
				</div>
				<button type="button" class="button button-primary woo-campaign-add-product-row"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Add product', 'now-campaign-storefronts' ); ?></button>
			</div>

			<div class="nowcastf-products-table-wrap">
				<table class="widefat woo-campaign-products-table">
					<thead><tr>
						<th><?php esc_html_e( 'Product / Variation', 'now-campaign-storefronts' ); ?></th>
						<th><?php esc_html_e( 'Woo price', 'now-campaign-storefronts' ); ?></th>
						<th><?php esc_html_e( 'Campaign price', 'now-campaign-storefronts' ); ?></th>
						<th><?php esc_html_e( 'Availability', 'now-campaign-storefronts' ); ?></th>
						<th><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'now-campaign-storefronts' ); ?></span></th>
					</tr></thead>
					<tbody class="nowcastf-product-rows">
					<?php foreach ( $rows as $row ) : $this->renderRow( $row->saleableId(), $row->campaignPrice, $row->status, $row->displayOrder ); endforeach; ?>
					</tbody>
				</table>
				<div class="nowcastf-products-empty" data-woo-campaign-products-empty <?php echo $rows ? 'hidden' : ''; ?>>
					<span class="dashicons dashicons-products"></span>
					<strong><?php esc_html_e( 'No campaign products yet', 'now-campaign-storefronts' ); ?></strong>
					<p><?php esc_html_e( 'Add products and set the price customers should receive inside this campaign.', 'now-campaign-storefronts' ); ?></p>
				</div>
			</div>

			<div class="nowcastf-products-footer">
				<span><?php esc_html_e( 'WooCommerce remains the source of truth for stock, SKU, images and product data.', 'now-campaign-storefronts' ); ?></span>
				<button type="button" class="button woo-campaign-add-product-row"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Add another product', 'now-campaign-storefronts' ); ?></button>
			</div>
			<template id="tmpl-woo-campaign-product-row"><?php $this->renderRow( 0, '', 'active', 9999 ); ?></template>
		</div>
		<?php
	}

	public function save( int $postId, \WP_Post $post ): void {
		if ( wp_is_post_revision( $postId ) || wp_is_post_autosave( $postId ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$rawSaleableIds = isset( $_POST['nowcastf_saleable_id'] ) ? (array) wp_unslash( $_POST['nowcastf_saleable_id'] ) : [];
		$rawPrices      = isset( $_POST['nowcastf_price'] ) ? (array) wp_unslash( $_POST['nowcastf_price'] ) : [];
		$rawStatuses    = isset( $_POST['nowcastf_product_status'] ) ? (array) wp_unslash( $_POST['nowcastf_product_status'] ) : [];
		$rawOrders      = isset( $_POST['nowcastf_display_order'] ) ? (array) wp_unslash( $_POST['nowcastf_display_order'] ) : [];

		$saleableIds = array_map( 'absint', $rawSaleableIds );
		$prices      = array_map( 'wc_format_decimal', $rawPrices );
		$statuses    = array_map( 'sanitize_key', $rawStatuses );
		$orders      = array_map( 'absint', $rawOrders );
		$input = [];
		foreach ( $saleableIds as $index => $saleableId ) {
			if ( $saleableId <= 0 ) {
				continue;
			}
			$input[] = [
				'saleable_id'    => $saleableId,
				'campaign_price' => $prices[ $index ] ?? '',
				'status'         => $statuses[ $index ] ?? 'active',
				'display_order'  => $orders[ $index ] ?? $index,
			];
		}
		$this->service->replace( $postId, $input );
	}

	private function renderRow( int $saleableId, string $campaignPrice, string $status, int $displayOrder ): void {
		$product = $saleableId > 0 ? $this->products->get( $saleableId ) : null;
		$label = $product ? wp_strip_all_tags( $product->get_formatted_name() ) : '';
		$rawPrice = $product ? (float) $product->get_price( 'edit' ) : 0.0;
		$wooPrice = $product ? wc_price( $rawPrice ) : '—';
		$sku = $product ? (string) $product->get_sku() : '';
		$stock = $product ? wc_get_stock_html( $product ) : '';
		?>
		<tr class="nowcastf-product-row <?php echo $saleableId <= 0 ? 'is-template' : ''; ?>" data-order="<?php echo esc_attr( (string) $displayOrder ); ?>">
			<td class="nowcastf-col-product">
				<input type="hidden" name="nowcastf_saleable_id[]" value="<?php echo esc_attr( (string) $saleableId ); ?>" class="nowcastf-saleable-id">
				<input type="hidden" name="nowcastf_display_order[]" value="<?php echo esc_attr( (string) $displayOrder ); ?>" class="nowcastf-display-order">
				<div class="nowcastf-product-display <?php echo $saleableId <= 0 ? 'is-empty' : ''; ?>">
					<div class="nowcastf-product-name"><?php echo esc_html( $label ?: __( 'Select product…', 'now-campaign-storefronts' ) ); ?></div>
					<div class="nowcastf-sku"><?php echo esc_html( $sku ? sprintf( __( 'SKU: %s', 'now-campaign-storefronts' ), $sku ) : '' ); ?></div>
					<div class="nowcastf-stock"><?php echo wp_kses_post( $stock ); ?></div>
				</div>
			</td>
			<td>
				<div class="nowcastf-price-reference" data-woo-base-price="<?php echo esc_attr( (string) $rawPrice ); ?>">
					<strong class="nowcastf-woo-price"><?php echo wp_kses_post( $wooPrice ); ?></strong>
				</div>
			</td>
			<td>
				<label class="nowcastf-price-input-wrap">
					<span class="screen-reader-text"><?php esc_html_e( 'Campaign price', 'now-campaign-storefronts' ); ?></span>
					<input type="number" min="0.00000001" step="0.01" name="nowcastf_price[]" value="<?php echo esc_attr( $campaignPrice ); ?>" required data-woo-campaign-price-input>
				</label>
				<span class="nowcastf-saving-preview" data-woo-campaign-saving-preview></span>
			</td>
			<td>
				<select name="nowcastf_product_status[]" class="nowcastf-status-select">
					<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'now-campaign-storefronts' ); ?></option>
					<option value="paused" <?php selected( $status, 'paused' ); ?>><?php esc_html_e( 'Paused', 'now-campaign-storefronts' ); ?></option>
				</select>
			</td>
			<td class="nowcastf-product-actions"><button type="button" class="button-link-delete woo-campaign-remove-product-row" aria-label="<?php esc_attr_e( 'Remove product', 'now-campaign-storefronts' ); ?>"><span class="dashicons dashicons-trash"></span></button></td>
		</tr>
		<?php
	}

	public function ajaxGetProductVariations(): void {
		check_ajax_referer( 'nowcastf_admin', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$productId = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
		$product = wc_get_product( $productId );
		if ( ! $product ) {
			wp_send_json_error( [ 'message' => 'Product not found' ], 404 );
		}

		if ( $product->is_type( 'variable' ) ) {
			$variations = [];
			foreach ( $product->get_children() as $childId ) {
				$variation = wc_get_product( $childId );
				if ( ! $variation instanceof \WC_Product_Variation ) {
					continue;
				}
				$variations[] = [
					'variation_id' => $variation->get_id(),
					'label'        => wc_get_formatted_variation( $variation, true, false, true ),
					'woo_price'    => wc_price( (float) $variation->get_price( 'edit' ) ),
					'raw_price'    => (float) $variation->get_price( 'edit' ),
					'sku'          => $variation->get_sku(),
					'stock'        => wc_get_stock_html( $variation ),
				];
			}
			wp_send_json_success( [
				'is_variable' => true,
				'parent_id'   => $product->get_id(),
				'parent_name' => $product->get_name(),
				'variations'  => $variations,
			] );
		}

		$normalized = $this->products->normalizeSaleable( $productId );
		if ( ! $normalized ) {
			wp_send_json_error( [ 'message' => 'Product is not saleable as campaign item' ], 400 );
		}

		wp_send_json_success( [
			'is_variable' => false,
			'saleable_id' => $productId,
			'name'        => wp_strip_all_tags( $product->get_formatted_name() ),
			'woo_price'   => wc_price( (float) $product->get_price( 'edit' ) ),
			'raw_price'   => (float) $product->get_price( 'edit' ),
			'sku'         => $product->get_sku(),
			'stock'       => wc_get_stock_html( $product ),
		] );
	}
}
