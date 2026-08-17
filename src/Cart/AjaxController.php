<?php

namespace WooCampaign\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AjaxController {
	private const NONCE_ACTION = 'woo_campaign_cart';

	public function __construct( private CartService $cart ) {}

	public function register(): void {
		foreach ( [ 'add', 'add_many', 'update', 'remove', 'get' ] as $action ) {
			add_action( 'wp_ajax_woo_campaign_' . $action . '_cart', [ $this, $action ] );
			add_action( 'wp_ajax_nopriv_woo_campaign_' . $action . '_cart', [ $this, $action ] );
		}
	}

	public static function nonce(): string {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	public function add(): void {
		$this->guard();
		try {
			$campaignId        = absint( $_POST['campaign_id'] ?? 0 );
			$campaignProductId = absint( $_POST['campaign_product_id'] ?? 0 );
			$quantity          = absint( $_POST['quantity'] ?? 1 );

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
		$this->guard();
		try {
			$raw   = wp_unslash( $_POST['items'] ?? '[]' );
			$items = json_decode( is_string( $raw ) ? $raw : '[]', true );
			if ( ! is_array( $items ) ) {
				throw new \RuntimeException( __( 'Invalid campaign items.', 'now-campaign-storefronts' ) );
			}

			$addedItems = $this->cart->addMany( absint( $_POST['campaign_id'] ?? 0 ), $items );
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
		$this->guard();
		try {
			$key = wc_clean( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
			$this->cart->update( $key, absint( $_POST['quantity'] ?? 0 ) );
			$snapshot = $this->cart->snapshot();
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
			return;
		}

		wp_send_json_success( $snapshot );
	}

	public function remove(): void {
		$this->guard();
		try {
			$key = wc_clean( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
			$this->cart->remove( $key );
			$snapshot = $this->cart->snapshot();
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
			return;
		}

		wp_send_json_success( $snapshot );
	}

	public function get(): void {
		$this->guard();
		wp_send_json_success( $this->cart->snapshot() );
	}

	private function guard(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => __( 'Cart is unavailable.', 'now-campaign-storefronts' ) ], 503 );
		}
	}

	/**
	 * Emit WooCommerce AJAX add-to-cart lifecycle actions in a scoped $_POST context.
	 *
	 * @param array{cart_item_key: string, product_id: int, variation_id: int, quantity: int, campaign_product_id: int} $addedItem
	 * @return array{fragments: array<string, string>, cart_hash: string}
	 */
	private function emitSingleCompatibility( array $addedItem ): array {
		$originalPost = $_POST;
		$_POST['product_id'] = $addedItem['product_id'];
		$_POST['quantity']   = $addedItem['quantity'];
		if ( ! empty( $addedItem['variation_id'] ) ) {
			$_POST['variation_id'] = $addedItem['variation_id'];
		} else {
			unset( $_POST['variation_id'] );
		}

		try {
			do_action( 'woocommerce_ajax_added_to_cart', $addedItem['product_id'] );

			$effectiveId = ! empty( $addedItem['variation_id'] ) ? $addedItem['variation_id'] : $addedItem['product_id'];
			do_action( 'internal_woocommerce_cart_item_added_from_user_request', $effectiveId, $addedItem['quantity'] );

			return $this->buildFragmentsData();
		} finally {
			$_POST = $originalPost;
		}
	}

	/**
	 * Emit WooCommerce AJAX add-to-cart lifecycle actions for batch items in a scoped $_POST context.
	 *
	 * @param list<array{cart_item_key: string, product_id: int, variation_id: int, quantity: int, campaign_product_id: int}> $addedItems
	 * @return array{fragments: array<string, string>, cart_hash: string}
	 */
	private function emitBatchCompatibility( array $addedItems ): array {
		$originalPost = $_POST;
		try {
			foreach ( $addedItems as $item ) {
				$_POST['product_id'] = $item['product_id'];
				$_POST['quantity']   = $item['quantity'];
				if ( ! empty( $item['variation_id'] ) ) {
					$_POST['variation_id'] = $item['variation_id'];
				} else {
					unset( $_POST['variation_id'] );
				}

				do_action( 'woocommerce_ajax_added_to_cart', $item['product_id'] );

				$effectiveId = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
				do_action( 'internal_woocommerce_cart_item_added_from_user_request', $effectiveId, $item['quantity'] );
			}

			return $this->buildFragmentsData();
		} finally {
			$_POST = $originalPost;
		}
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
