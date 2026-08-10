<?php

namespace WooCampaign\Reporting;

use WooCampaign\Campaign\Meta;
use WooCampaign\Campaign\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignReportShare {
	private const REPORT_CAMPAIGN_META = '_woo_campaign_report_campaign_id';

	public function __construct( private CampaignReportSecret $secret ) {}

	public function state( int $campaignId ): array {
		$key = (string) get_post_meta( $campaignId, Meta::REPORT_SHARE_KEY, true );
		$reportPost = $this->reportPost( $campaignId, false );
		$passwordSet = $reportPost instanceof \WP_Post && ! empty( $reportPost->post_password );
		if ( ! $passwordSet ) {
			$passwordSet = $this->hasLegacyCredential( $campaignId ) && '' !== $this->legacyPassword( $campaignId );
		}

		return [
			'enabled'      => (bool) get_post_meta( $campaignId, Meta::REPORT_ENABLED, true ),
			'share_key'    => $key,
			'url'          => $key !== '' ? $this->urlForKey( $key ) : '',
			'password_set' => $passwordSet,
			'enabled_at'   => absint( get_post_meta( $campaignId, Meta::REPORT_ENABLED_AT, true ) ),
		];
	}

	public function adminState( int $campaignId ): array {
		$reportPost = $this->reportPost( $campaignId, true );
		$state = $this->state( $campaignId );
		$password = $reportPost instanceof \WP_Post ? (string) $reportPost->post_password : $this->legacyPassword( $campaignId );
		$state['password'] = $password;
		$state['password_recoverable'] = ! empty( $password );
		return $state;
	}

	public function save( int $campaignId, bool $enabled, string $password = '' ): array|\WP_Error {
		if ( PostType::TYPE !== get_post_type( $campaignId ) ) {
			return new \WP_Error( 'invalid_campaign', __( 'Campaign not found.', 'wc-campaign' ) );
		}

		$password = (string) $password;
		if ( '0' === $password ) {
			return new \WP_Error( 'invalid_password', __( 'Use a sharing password other than 0.', 'wc-campaign' ) );
		}

		$reportPost = $this->reportPost( $campaignId, true );
		if ( '' !== $password ) {
			$reportPost = $this->ensureReportPost( $campaignId, $password );
			if ( is_wp_error( $reportPost ) ) {
				return $reportPost;
			}
			$this->cleanupLegacyCredentials( $campaignId );
		}

		if ( $enabled && ( ! $reportPost instanceof \WP_Post || empty( $reportPost->post_password ) ) ) {
			return new \WP_Error( 'password_required', __( 'Set a password before enabling the external report.', 'wc-campaign' ) );
		}

		if ( $enabled ) {
			$this->ensureKey( $campaignId );
			if ( ! get_post_meta( $campaignId, Meta::REPORT_ENABLED_AT, true ) ) {
				update_post_meta( $campaignId, Meta::REPORT_ENABLED_AT, time() );
			}
		}

		update_post_meta( $campaignId, Meta::REPORT_ENABLED, $enabled ? 1 : 0 );
		return $this->adminState( $campaignId );
	}

	public function regenerate( int $campaignId ): array|\WP_Error {
		if ( PostType::TYPE !== get_post_type( $campaignId ) ) {
			return new \WP_Error( 'invalid_campaign', __( 'Campaign not found.', 'wc-campaign' ) );
		}
		$key = $this->generateKey();
		update_post_meta( $campaignId, Meta::REPORT_SHARE_KEY, $key );
		return $this->adminState( $campaignId );
	}

	public function findByKey( string $key ): int {
		$key = sanitize_text_field( $key );
		if ( $key === '' ) {
			return 0;
		}
		$ids = get_posts(
			[
				'post_type'      => PostType::TYPE,
				'post_status'    => [ 'publish', 'draft', 'private' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'     => Meta::REPORT_SHARE_KEY,
						'value'   => $key,
						'compare' => '=',
					],
				],
			]
		);
		return $ids ? absint( $ids[0] ) : 0;
	}

	public function isEnabled( int $campaignId ): bool {
		return (bool) get_post_meta( $campaignId, Meta::REPORT_ENABLED, true );
	}

	public function key( int $campaignId ): string {
		return (string) get_post_meta( $campaignId, Meta::REPORT_SHARE_KEY, true );
	}

	public function urlForKey( string $key ): string {
		return home_url( '/campaign-report/' . rawurlencode( $key ) . '/' );
	}

	/**
	 * Return the hidden WordPress post whose post_password protects this report.
	 * Existing custom-auth installs are migrated lazily when their recoverable
	 * legacy secret is available.
	 */
	public function reportPost( int $campaignId, bool $migrateLegacy = true ): ?\WP_Post {
		$postId = absint( get_post_meta( $campaignId, Meta::REPORT_POST_ID, true ) );
		if ( $postId > 0 ) {
			$post = get_post( $postId );
			if ( $post instanceof \WP_Post && CampaignReportPostType::TYPE === $post->post_type ) {
				return $post;
			}
			delete_post_meta( $campaignId, Meta::REPORT_POST_ID );
		}

		$ids = get_posts(
			[
				'post_type'      => CampaignReportPostType::TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::REPORT_CAMPAIGN_META,
				'meta_value'     => $campaignId,
			]
		);
		if ( $ids ) {
			$post = get_post( absint( $ids[0] ) );
			if ( $post instanceof \WP_Post ) {
				update_post_meta( $campaignId, Meta::REPORT_POST_ID, $post->ID );
				return $post;
			}
		}

		if ( ! $migrateLegacy || ! $this->hasLegacyCredential( $campaignId ) ) {
			return null;
		}

		$password = $this->legacyPassword( $campaignId );
		if ( '' === $password ) {
			return null;
		}

		$migrated = $this->ensureReportPost( $campaignId, $password );
		if ( is_wp_error( $migrated ) ) {
			return null;
		}
		$this->cleanupLegacyCredentials( $campaignId );
		return $migrated;
	}

	public function deleteForCampaign( int $campaignId ): void {
		$post = $this->reportPost( $campaignId, false );
		if ( $post instanceof \WP_Post ) {
			wp_delete_post( $post->ID, true );
		}
		delete_post_meta( $campaignId, Meta::REPORT_POST_ID );
		delete_post_meta( $campaignId, Meta::REPORT_PASSWORD_HASH );
		delete_post_meta( $campaignId, Meta::REPORT_PASSWORD_SECRET );
	}

	private function ensureReportPost( int $campaignId, string $password ): \WP_Post|\WP_Error {
		if ( empty( $password ) ) {
			return new \WP_Error( 'invalid_password', __( 'Use a non-empty sharing password other than 0.', 'wc-campaign' ) );
		}

		$existing = $this->reportPost( $campaignId, false );
		$title = sprintf( 'Campaign Report Password #%d', $campaignId );

		if ( $existing instanceof \WP_Post ) {
			$result = wp_update_post(
				[
					'ID'            => $existing->ID,
					'post_title'    => $title,
					'post_password' => $password,
				],
				true
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$post = get_post( $existing->ID );
			return $post instanceof \WP_Post ? $post : new \WP_Error( 'report_password_post_missing', __( 'Unable to load the report password record.', 'wc-campaign' ) );
		}

		$postId = wp_insert_post(
			[
				'post_type'     => CampaignReportPostType::TYPE,
				'post_status'   => 'publish',
				'post_title'    => $title,
				'post_content'  => '',
				'post_password' => $password,
			],
			true
		);
		if ( is_wp_error( $postId ) ) {
			return $postId;
		}

		update_post_meta( $postId, self::REPORT_CAMPAIGN_META, $campaignId );
		update_post_meta( $campaignId, Meta::REPORT_POST_ID, $postId );
		$post = get_post( $postId );
		return $post instanceof \WP_Post ? $post : new \WP_Error( 'report_password_post_missing', __( 'Unable to load the report password record.', 'wc-campaign' ) );
	}

	private function ensureKey( int $campaignId ): string {
		$key = $this->key( $campaignId );
		if ( $key === '' ) {
			$key = $this->generateKey();
			update_post_meta( $campaignId, Meta::REPORT_SHARE_KEY, $key );
		}
		return $key;
	}

	private function generateKey(): string {
		return wp_generate_password( 36, false, false );
	}

	private function hasLegacyCredential( int $campaignId ): bool {
		return '' !== (string) get_post_meta( $campaignId, Meta::REPORT_PASSWORD_HASH, true )
			|| '' !== (string) get_post_meta( $campaignId, Meta::REPORT_PASSWORD_SECRET, true );
	}

	private function legacyPassword( int $campaignId ): string {
		$stored = (string) get_post_meta( $campaignId, Meta::REPORT_PASSWORD_SECRET, true );
		$password = $this->secret->decrypt( $stored );
		return empty( $password ) ? '' : $password;
	}

	private function cleanupLegacyCredentials( int $campaignId ): void {
		delete_post_meta( $campaignId, Meta::REPORT_PASSWORD_HASH );
		delete_post_meta( $campaignId, Meta::REPORT_PASSWORD_SECRET );
	}
}
