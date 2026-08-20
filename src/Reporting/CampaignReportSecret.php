<?php

namespace Bboyfan\NowCampaignStorefronts\Reporting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy compatibility helper.
 *
 * Pre-native-auth private builds stored the report sharing password in an
 * encrypted campaign meta value so the WooCommerce manager UI could display it.
 * Current builds use WordPress Core post_password as the only active report
 * password authority. This class remains solely to migrate those existing
 * encrypted values into the hidden CampaignReportPostType record once.
 */
final class CampaignReportSecret {
	private const PREFIX = 'v1:';
	private const CIPHER = 'aes-256-gcm';
	private const IV_BYTES = 12;
	private const TAG_BYTES = 16;
	private const AAD = 'nowcastf-report-password';

	public function encrypt( string $password ): string|\WP_Error {
		if ( '' === $password ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return new \WP_Error( 'report_secret_crypto_unavailable', __( 'Server encryption support is unavailable.', 'now-campaign-storefronts' ) );
		}

		try {
			$iv = random_bytes( self::IV_BYTES );
		} catch ( \Throwable $error ) {
			return new \WP_Error( 'report_secret_random_failed', __( 'Unable to generate secure report credentials.', 'now-campaign-storefronts' ) );
		}

		$tag = '';
		$ciphertext = openssl_encrypt(
			$password,
			self::CIPHER,
			$this->key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			self::AAD,
			self::TAG_BYTES
		);
		if ( false === $ciphertext || self::TAG_BYTES !== strlen( $tag ) ) {
			return new \WP_Error( 'report_secret_encrypt_failed', __( 'Unable to encrypt the report password.', 'now-campaign-storefronts' ) );
		}

		return self::PREFIX . base64_encode( $iv . $tag . $ciphertext );
	}

	public function decrypt( string $stored ): string {
		if ( '' === $stored || ! str_starts_with( $stored, self::PREFIX ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$payload = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true );
		if ( false === $payload || strlen( $payload ) <= self::IV_BYTES + self::TAG_BYTES ) {
			return '';
		}

		$iv = substr( $payload, 0, self::IV_BYTES );
		$tag = substr( $payload, self::IV_BYTES, self::TAG_BYTES );
		$ciphertext = substr( $payload, self::IV_BYTES + self::TAG_BYTES );
		$password = openssl_decrypt(
			$ciphertext,
			self::CIPHER,
			$this->key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			self::AAD
		);

		return false === $password ? '' : $password;
	}

	private function key(): string {
		return hash( 'sha256', wp_salt( 'auth' ) . '|woo-campaign-report-secret', true );
	}
}
