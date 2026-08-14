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
			$this->cart->add(
				absint( $_POST['campaign_id'] ?? 0 ),
				absint( $_POST['campaign_product_id'] ?? 0 ),
				absint( $_POST['quantity'] ?? 1 )
			);
			wp_send_json_success( $this->cart->snapshot() );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
		}
	}

	public function add_many(): void {
		$this->guard();
		try {
			$raw = wp_unslash( $_POST['items'] ?? '[]' );
			$items = json_decode( is_string( $raw ) ? $raw : '[]', true );
			if ( ! is_array( $items ) ) {
				throw new \RuntimeException( __( 'Invalid campaign items.', 'now-campaign-storefronts' ) );
			}
			$this->cart->addMany( absint( $_POST['campaign_id'] ?? 0 ), $items );
			wp_send_json_success( $this->cart->snapshot() );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
		}
	}

	public function update(): void {
		$this->guard();
		try {
			$key = wc_clean( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
			$this->cart->update( $key, absint( $_POST['quantity'] ?? 0 ) );
			wp_send_json_success( $this->cart->snapshot() );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
		}
	}

	public function remove(): void {
		$this->guard();
		try {
			$key = wc_clean( wp_unslash( $_POST['cart_item_key'] ?? '' ) );
			$this->cart->remove( $key );
			wp_send_json_success( $this->cart->snapshot() );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
		}
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
}
