<?php

namespace Bboyfan\NowCampaignStorefronts\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AjaxController {
	private const NONCE_ACTION = 'nowcastf_cart';

	public function __construct( private CartService $cart ) {}

	public function register(): void {
		foreach ( [ 'add', 'add_many', 'update', 'remove', 'get' ] as $action ) {
			add_action( 'wp_ajax_nowcastf_' . $action . '_cart', [ $this, $action ] );
			add_action( 'wp_ajax_nopriv_nowcastf_' . $action . '_cart', [ $this, $action ] );
		}
	}

	public static function nonce(): string {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	public function add(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$this->guardCart();

		try {
			$campaignId        = absint( wp_unslash( $_POST['campaign_id'] ?? 0 ) );
			$campaignProductId = absint( wp_unslash( $_POST['campaign_product_id'] ?? 0 ) );
			$quantity          = max( 1, absint( wp_unslash( $_POST['quantity'] ?? 1 ) ) );

			$addedItem = $this->cart->add( $campaignId, $campaignProductId, $quantity );
			$compat    = $this->emitSingleCompatibility( $addedItem );

			$response = [
				'snapshot'    => $this->cart->snapshot(),
				'fragments'   => $compat['fragments'],
				'cart_hash'   => $compat['cart_hash'],
				'added_items' => [ $addedItem ],
			];
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
			return;
		}

		wp_send_json_success( $response );
	}

	public function add_many(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$this->guardCart();

		try {
			$campaignId = absint( wp_unslash( $_POST['campaign_id'] ?? 0 ) );
			$raw        = isset( $_POST['items'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['items'] ) ) : '[]';
			$items      = json_decode( $raw, true );
			if ( ! is_array( $items ) || JSON_ERROR_NONE !== json_last_error() ) {
				throw new \RuntimeException( __( 'Invalid campaign items.', 'now-campaign-storefronts' ) );
			}

			$sanitizedItems = [];
			foreach ( $items as $rawItem ) {
				if ( ! is_array( $rawItem ) ) {
					continue;
				}
				$cpid = absint( $rawItem['campaign_product_id'] ?? 0 );
				$qty  = absint( $rawItem['quantity'] ?? 0 );
				if ( $cpid > 0 && $qty > 0 ) {
					$sanitizedItems[] = [
						'campaign_product_id' => $cpid,
						'quantity'            => $qty,
					];
				}
			}

			if ( ! $sanitizedItems ) {
				throw new \RuntimeException( __( 'Select at least one campaign item.', 'now-campaign-storefronts' ) );
			}

			$addedItems = $this->cart->addMany( $campaignId, $sanitizedItems );
			$compat     = $this->emitBatchCompatibility( $addedItems );

			$response = [
				'snapshot'    => $this->cart->snapshot(),
				'fragments'   => $compat['fragments'],
				'cart_hash'   => $compat['cart_hash'],
				'added_items' => $addedItems,
			];
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
			return;
		}

		wp_send_json_success( $response );
	}

	public function update(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$this->guardCart();

		try {
			$key      = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
			$quantity = absint( wp_unslash( $_POST['quantity'] ?? 0 ) );
			$this->cart->update( $key, $quantity );
			$snapshot = $this->cart->snapshot();
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
			return;
		}

		wp_send_json_success( $snapshot );
	}

	public function remove(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$this->guardCart();

		try {
			$key = sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
			$this->cart->remove( $key );
			$snapshot = $this->cart->snapshot();
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
			return;
		}

		wp_send_json_success( $snapshot );
	}

	public function get(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$this->guardCart();

		wp_send_json_success( $this->cart->snapshot() );
	}

	private function guardCart(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => __( 'Cart is unavailable.', 'now-campaign-storefronts' ) ], 503 );
		}
	}

	/**
	 * Emit WooCommerce AJAX add-to-cart lifecycle actions in a scoped minimal $_POST context.
	 *
	 * @param array{cart_item_key: string, product_id: int, variation_id: int, quantity: int, campaign_product_id: int} $addedItem
	 * @return array{fragments: array<string, string>, cart_hash: string}
	 */
	private function emitSingleCompatibility( array $addedItem ): array {
		$_POST = $this->buildCompatibilityPostContext( $addedItem );

		do_action( 'woocommerce_ajax_added_to_cart', absint( $addedItem['product_id'] ) );

		$effectiveId = ! empty( $addedItem['variation_id'] ) ? absint( $addedItem['variation_id'] ) : absint( $addedItem['product_id'] );
		do_action( 'nowcastf_cart_item_added_from_user_request', $effectiveId, max( 1, absint( $addedItem['quantity'] ) ) );

		return $this->buildFragmentsData();
	}

	/**
	 * Emit WooCommerce AJAX add-to-cart lifecycle actions for batch items in a scoped minimal $_POST context.
	 *
	 * @param list<array{cart_item_key: string, product_id: int, variation_id: int, quantity: int, campaign_product_id: int}> $addedItems
	 * @return array{fragments: array<string, string>, cart_hash: string}
	 */
	private function emitBatchCompatibility( array $addedItems ): array {
		foreach ( $addedItems as $item ) {
			$_POST = $this->buildCompatibilityPostContext( $item );

			do_action( 'woocommerce_ajax_added_to_cart', absint( $item['product_id'] ) );

			$effectiveId = ! empty( $item['variation_id'] ) ? absint( $item['variation_id'] ) : absint( $item['product_id'] );
			do_action( 'nowcastf_cart_item_added_from_user_request', $effectiveId, max( 1, absint( $item['quantity'] ) ) );
		}

		return $this->buildFragmentsData();
	}

	/**
	 * Build minimal sanitized compatibility $_POST context.
	 *
	 * @param array{product_id?: int, quantity?: int, variation_id?: int} $item
	 * @return array<string, int>
	 */
	private function buildCompatibilityPostContext( array $item ): array {
		$context = [
			'product_id' => absint( $item['product_id'] ?? 0 ),
			'quantity'   => max( 1, absint( $item['quantity'] ?? 1 ) ),
		];

		$variationId = absint( $item['variation_id'] ?? 0 );
		if ( $variationId > 0 ) {
			$context['variation_id'] = $variationId;
		}

		return $context;
	}

	/**
	 * Build Woo-compatible fragments and cart_hash identical to WC_AJAX::get_refreshed_fragments()
	 * without triggering early exit.
	 *
	 * @return array{fragments: array<string, string>, cart_hash: string}
	 */
	private function buildFragmentsData(): array {
		$miniCart = '';
		if ( function_exists( 'woocommerce_mini_cart' ) ) {
			ob_start();
			woocommerce_mini_cart();
			$miniCart = ob_get_clean();
		}

		$defaultFragments = [
			'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $miniCart . '</div>',
		];

		$fragments = apply_filters( 'woocommerce_add_to_cart_fragments', $defaultFragments );

		$cartHash = '';
		if ( function_exists( 'WC' ) && WC()->cart && method_exists( WC()->cart, 'get_cart_hash' ) ) {
			$cartHash = (string) WC()->cart->get_cart_hash();
		}

		return [
			'fragments' => is_array( $fragments ) ? $fragments : $defaultFragments,
			'cart_hash' => $cartHash,
		];
	}
}
