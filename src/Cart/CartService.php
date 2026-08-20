<?php

namespace Bboyfan\NowCampaignStorefronts\Cart;

use Bboyfan\NowCampaignStorefronts\Campaign\CampaignRepository;
use Bboyfan\NowCampaignStorefronts\CampaignProduct\Repository as CampaignProductRepository;
use Bboyfan\NowCampaignStorefronts\Pricing\CampaignPriceResolver;
use Bboyfan\NowCampaignStorefronts\Product\ProductAdapter;

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

	/**
	 * @return array{
	 *     cart_item_key: string,
	 *     product_id: int,
	 *     variation_id: int,
	 *     quantity: int,
	 *     campaign_product_id: int,
	 * }
	 */
	public function add( int $campaignId, int $campaignProductId, int $quantity ): array {
		$prepared = $this->prepareItem( $campaignId, $campaignProductId, max( 1, $quantity ) );
		return $this->addPreparedItem( $prepared );
	}

	/**
	 * Add multiple CampaignProducts in one request. All items are validated first;
	 * if a later Woo add fails, lines added by this call are rolled back.
	 *
	 * @return list<array{
	 *     cart_item_key: string,
	 *     product_id: int,
	 *     variation_id: int,
	 *     quantity: int,
	 *     campaign_product_id: int,
	 * }>
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
				throw new \RuntimeException( esc_html__( 'A campaign item was submitted more than once.', 'now-campaign-storefronts' ) );
			}
			$seen[ $campaignProductId ] = true;
			$prepared[] = $this->prepareItem( $campaignId, $campaignProductId, $quantity );
		}
		if ( ! $prepared ) {
			throw new \RuntimeException( esc_html__( 'Select at least one campaign item.', 'now-campaign-storefronts' ) );
		}

		$initialQuantities = [];
		foreach ( WC()->cart->get_cart() as $key => $cartItem ) {
			$initialQuantities[ $key ] = (int) ( $cartItem['quantity'] ?? 0 );
		}

		$addedItems = [];
		try {
			foreach ( $prepared as $item ) {
				$addedItems[] = $this->addPreparedItem( $item );
			}
		} catch ( \Throwable $e ) {
			foreach ( $addedItems as $added ) {
				$key     = $added['cart_item_key'];
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
		return $addedItems;
	}

	public function update( string $key, int $quantity ): void {
		if ( ! WC()->cart->set_quantity( $key, max( 0, $quantity ), true ) ) {
			throw new \RuntimeException( esc_html__( 'Unable to update the cart item.', 'now-campaign-storefronts' ) );
		}
	}

	public function remove( string $key ): void {
		if ( ! WC()->cart->remove_cart_item( $key ) ) {
			throw new \RuntimeException( esc_html__( 'Unable to remove the cart item.', 'now-campaign-storefronts' ) );
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
			throw new \RuntimeException( esc_html__( 'This campaign item is no longer available.', 'now-campaign-storefronts' ) );
		}
		$item = $this->resolver->resolve( $campaignId, $campaignProductId, $stored->productId, $stored->variationId );
		if ( ! $item ) {
			throw new \RuntimeException( esc_html__( 'This campaign item is no longer available.', 'now-campaign-storefronts' ) );
		}
		$product = $this->products->get( $item->saleableId() );
		if ( ! $product || ! $this->products->isPurchasable( $product ) ) {
			throw new \RuntimeException( esc_html__( 'This product is not currently purchasable.', 'now-campaign-storefronts' ) );
		}
		return [ 'campaign_id' => $campaignId, 'campaign_product_id' => $campaignProductId, 'quantity' => $quantity, 'item' => $item, 'product' => $product ];
	}

	/**
	 * @return array{
	 *     cart_item_key: string,
	 *     product_id: int,
	 *     variation_id: int,
	 *     quantity: int,
	 *     campaign_product_id: int,
	 * }
	 */
	private function addPreparedItem( array $prepared ): array {
		$item              = $prepared['item'];
		$product           = $prepared['product'];
		$campaignId        = (int) $prepared['campaign_id'];
		$campaignProductId = (int) $prepared['campaign_product_id'];
		$quantity          = (int) $prepared['quantity'];
		$variation         = $item->variationId > 0 && $product instanceof \WC_Product_Variation ? $product->get_variation_attributes() : [];
		$cartItemData      = [
			'_woo_campaign_id'         => $campaignId,
			'_woo_campaign_product_id' => $campaignProductId,
			'_woo_campaign_price'      => wc_format_decimal( $item->campaignPrice ),
			'_woo_campaign_base_price' => $this->products->basePrice( $product ),
			'_woo_campaign_title'      => $this->campaigns->title( $campaignId ),
			'_woo_campaign_slug'       => $this->campaigns->slug( $campaignId ),
		];
		$key = WC()->cart->add_to_cart( $item->productId, $quantity, $item->variationId, $variation, $cartItemData );
		if ( ! $key ) {
			throw new \RuntimeException( esc_html__( 'Unable to add this campaign product to the cart.', 'now-campaign-storefronts' ) );
		}
		return [
			'cart_item_key'       => (string) $key,
			'product_id'          => (int) $item->productId,
			'variation_id'        => (int) $item->variationId,
			'quantity'            => $quantity,
			'campaign_product_id' => $campaignProductId,
		];
	}
}
