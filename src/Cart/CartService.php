<?php

namespace WooCampaign\Cart;

use WooCampaign\Campaign\CampaignRepository;
use WooCampaign\CampaignProduct\Repository as CampaignProductRepository;
use WooCampaign\Pricing\CampaignPriceResolver;
use WooCampaign\Product\ProductAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CartService {
	public function __construct(
		private CampaignPriceResolver $resolver,
		private CampaignProductRepository $campaignProducts,
		private ProductAdapter $products,
		private CampaignRepository $campaigns,
	) {}

	public function add( int $campaignId, int $campaignProductId, int $quantity ): string {
		$prepared = $this->prepareItem( $campaignId, $campaignProductId, max( 1, $quantity ) );
		return $this->addPreparedItem( $prepared );
	}

	/**
	 * Add multiple CampaignProducts in one request. All items are validated first;
	 * if a later Woo add fails, lines added by this call are rolled back.
	 */
	public function addMany( int $campaignId, array $items ): array {
		$prepared = [];
		$seen = [];
		foreach ( $items as $raw ) {
			$campaignProductId = absint( $raw['campaign_product_id'] ?? 0 );
			$quantity = absint( $raw['quantity'] ?? 0 );
			if ( $campaignProductId <= 0 || $quantity <= 0 ) {
				continue;
			}
			if ( isset( $seen[ $campaignProductId ] ) ) {
				throw new \RuntimeException( __( 'A campaign item was submitted more than once.', 'wc-campaign' ) );
			}
			$seen[ $campaignProductId ] = true;
			$prepared[] = $this->prepareItem( $campaignId, $campaignProductId, $quantity );
		}
		if ( ! $prepared ) {
			throw new \RuntimeException( __( 'Select at least one campaign item.', 'wc-campaign' ) );
		}

		$initialQuantities = [];
		foreach ( WC()->cart->get_cart() as $key => $cartItem ) {
			$initialQuantities[ $key ] = (int) ( $cartItem['quantity'] ?? 0 );
		}

		$addedKeys = [];
		try {
			foreach ( $prepared as $item ) {
				$addedKeys[] = $this->addPreparedItem( $item );
			}
		} catch ( \Throwable $e ) {
			foreach ( array_unique( $addedKeys ) as $key ) {
				$prevQty = $initialQuantities[ $key ] ?? 0;
				if ( $prevQty > 0 ) {
					WC()->cart->set_quantity( $key, $prevQty, true );
				} else {
					WC()->cart->remove_cart_item( $key );
				}
			}
			WC()->cart->calculate_totals();
			throw $e;
		}
		return $addedKeys;
	}

	public function update( string $key, int $quantity ): void {
		if ( ! WC()->cart->set_quantity( $key, max( 0, $quantity ), true ) ) {
			throw new \RuntimeException( __( 'Unable to update the cart item.', 'wc-campaign' ) );
		}
	}

	public function remove( string $key ): void {
		if ( ! WC()->cart->remove_cart_item( $key ) ) {
			throw new \RuntimeException( __( 'Unable to remove the cart item.', 'wc-campaign' ) );
		}
	}

	public function snapshot(): array {
		WC()->cart->calculate_totals();
		WC()->cart->set_session();
		if ( WC()->session ) {
			WC()->session->save_data();
		}
		$items = [];
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = $item['data'];
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$imageId = $product->get_image_id();
			$items[] = [
				'key'       => $key,
				'name'      => $product->get_name(),
				'quantity'  => (int) $item['quantity'],
				'image'     => $imageId ? (string) wp_get_attachment_image_url( $imageId, 'thumbnail' ) : '',
				'lineTotal' => wc_price( (float) $item['line_total'] + (float) $item['line_tax'] ),
				'campaign'  => ! empty( $item['_woo_campaign_id'] ),
			];
		}
		$totals = WC()->cart->get_totals();
		return [
			'count'       => WC()->cart->get_cart_contents_count(),
			'items'       => $items,
			'subtotal'    => wc_price( (float) ( $totals['subtotal'] ?? 0 ) ),
			'discount'    => wc_price( (float) ( $totals['discount_total'] ?? 0 ) + (float) ( $totals['discount_tax'] ?? 0 ) ),
			'total'       => WC()->cart->get_total(),
			'cartUrl'     => wc_get_cart_url(),
			'checkoutUrl' => wc_get_checkout_url(),
		];
	}

	private function prepareItem( int $campaignId, int $campaignProductId, int $quantity ): array {
		$stored = $this->campaignProducts->find( $campaignProductId );
		if ( ! $stored ) {
			throw new \RuntimeException( __( 'This campaign item is no longer available.', 'wc-campaign' ) );
		}
		$item = $this->resolver->resolve( $campaignId, $campaignProductId, $stored->productId, $stored->variationId );
		if ( ! $item ) {
			throw new \RuntimeException( __( 'This campaign item is no longer available.', 'wc-campaign' ) );
		}
		$product = $this->products->get( $item->saleableId() );
		if ( ! $product || ! $this->products->isPurchasable( $product ) ) {
			throw new \RuntimeException( __( 'This product is not currently purchasable.', 'wc-campaign' ) );
		}
		return [ 'campaign_id' => $campaignId, 'campaign_product_id' => $campaignProductId, 'quantity' => $quantity, 'item' => $item, 'product' => $product ];
	}

	private function addPreparedItem( array $prepared ): string {
		$item = $prepared['item'];
		$product = $prepared['product'];
		$campaignId = (int) $prepared['campaign_id'];
		$campaignProductId = (int) $prepared['campaign_product_id'];
		$variation = $item->variationId > 0 && $product instanceof \WC_Product_Variation ? $product->get_variation_attributes() : [];
		$cartItemData = [
			'_woo_campaign_id'         => $campaignId,
			'_woo_campaign_product_id' => $campaignProductId,
			'_woo_campaign_price'      => wc_format_decimal( $item->campaignPrice ),
			'_woo_campaign_base_price' => $this->products->basePrice( $product ),
			'_woo_campaign_title'      => $this->campaigns->title( $campaignId ),
			'_woo_campaign_slug'       => $this->campaigns->slug( $campaignId ),
		];
		$key = WC()->cart->add_to_cart( $item->productId, (int) $prepared['quantity'], $item->variationId, $variation, $cartItemData );
		if ( ! $key ) {
			throw new \RuntimeException( __( 'Unable to add this campaign product to the cart.', 'wc-campaign' ) );
		}
		return $key;
	}
}
