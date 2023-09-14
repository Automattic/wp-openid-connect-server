<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\OpenID\Storage\AuthorizationCodeInterface;

class AuthorizationCodeStorage implements AuthorizationCodeInterface {
	const META_KEY_PREFIX = 'oidc';

	private static array $authorization_code_data = array(
		'client_id'    => 'string', // client identifier.
		'redirect_uri' => 'string', // redirect URI.
		'expires'      => 'int',    // expires as unix timestamp.
		'scope'        => 'string', // scope as space-separated string.
		'id_token'     => 'string', // The OpenID Connect id_token.
	);

	public function __construct() {
		add_action( 'oidc_cron_hook', array( $this, 'cleanupOldCodes' ) );
	}

	private function getUserIdByCode( $code ) {
		if ( empty( $code ) ) {
			return null;
		}

		$key = self::META_KEY_PREFIX . '_client_id_' . $code;

		$users = get_users(
			array(
				// Specifying blog_id does nothing for non-MultiSite installs. But for MultiSite installs, it allows you
				// to customize users of which site is supposed to be available for whatever sites
				// this plugin is meant to be activated on.
				'blog_id'      => apply_filters( 'oidc_auth_code_storage_blog_id', get_current_blog_id() ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'     => $key,
				// Using a meta_key EXISTS query is not slow, see https://github.com/WordPress/WordPress-Coding-Standards/issues/1871.
				'meta_compare' => 'EXISTS',
			)
		);

		if ( empty( $users ) ) {
			return null;
		}

		if ( count( $users ) > 1 ) {
			// This should never happen.
			// If it does, something is wrong, so it's best to not return any user.
			$debug_log     = "[CONCURRENTLOGINS] more than 1 user found for code: $code.";
			$found         = 0;
			$found_user_id = 0;
			foreach ( $users as $user ) {
				if ( '' === get_user_meta( $user->ID, $key, true ) ) {
					$debug_log .= " ($user->ID empty meta) ";
				} else {
					++$found;
					$found_user_id = $user->ID; // only used when $found is 1, so overwrite is fine.
					$debug_log    .= " ($user->ID meta exists) ";
				}
			}

			if ( 1 === $found ) {
				$debug_log .= ' RECOVERED ';
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions
				error_log( $debug_log . print_r( $users, true ) );
				return $found_user_id;
			}

			$debug_log .= " FAILED (found:$found)";
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			error_log( $debug_log . print_r( $users, true ) );
			return null;
		}

		$user = $users[0];

		// Double-check that the user actually has the meta key.
		if ( '' === get_user_meta( $user, $key, true ) ) {
			return null;
		}

		return absint( $user->ID );
	}

	public function getAuthorizationCode( $code ) {
		$user_id = $this->getUserIdByCode( $code );
		if ( empty( $user_id ) ) {
			return null;
		}

		$user = new \WP_User( $user_id );

		$authorization_code = array(
			'user_id' => $user->user_login,
			'code'    => $code,
		);
		foreach ( array_keys( self::$authorization_code_data ) as $key ) {
			$authorization_code[ $key ] = get_user_meta( $user_id, self::META_KEY_PREFIX . '_' . $key . '_' . $code, true );
		}

		return $authorization_code;
	}

	public function setAuthorizationCode( $code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null ) {
		if ( empty( $code ) ) {
			return;
		}

		$user = get_user_by( 'login', $user_id ); // We have chosen WordPress' user_login as the user identifier for OIDC context.

		if ( $user ) {
			foreach ( self::$authorization_code_data as $key => $data_type ) {
				if ( 'int' === $data_type ) {
					$value = absint( $$key );
				} else {
					$value = sanitize_text_field( $$key );
				}

				update_user_meta( $user->ID, self::META_KEY_PREFIX . '_' . $key . '_' . $code, $value );
			}
		}
	}

	public function expireAuthorizationCode( $code ) {
		$user_id = $this->getUserIdByCode( $code );
		if ( empty( $user_id ) ) {
			return null;
		}

		foreach ( array_keys( self::$authorization_code_data ) as $key ) {
			delete_user_meta( $user_id, self::META_KEY_PREFIX . '_' . $key . '_' . $code );
		}
	}

	/**
	 * This function cleans up auth codes that are sitting in the database because of interrupted/abandoned OAuth flows.
	 */
	public function cleanupOldCodes() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE %s AND meta_value < %d",
				'oidc_expires_%',
				time() - 3600 // wait for an hour past expiry, to offer a chance at debug.
			)
		);
		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $row ) {
			$code = substr( $row->meta_key, strlen( 'oidc_expires_' ) );
			foreach ( array_keys( self::$authorization_code_data ) as $key ) {
				delete_user_meta( $row->user_id, self::META_KEY_PREFIX . '_' . $key . '_' . $code );
			}
		}
	}

	public static function uninstall() {
		global $wpdb;

		// Following query is only possible via a direct query since meta_key is not a fixed string
		// and since it only runs at uninstall, we don't need it cached.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE %s",
				'oidc_expires_%',
			)
		);
		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $row ) {
			$code = substr( $row->meta_key, strlen( 'oidc_expires_' ) );
			foreach ( array_keys( self::$authorization_code_data ) as $key ) {
				delete_user_meta( $row->user_id, self::META_KEY_PREFIX . '_' . $key . '_' . $code );
			}
		}
	}
}
