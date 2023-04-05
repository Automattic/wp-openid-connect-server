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
	/**
	* Constructor for the class.
	 *
	 * Adds an action to the 'oidc_cron_hook' hook to call the 'cleanupOldCodes' method.
	*/

	public function __construct() {
		add_action( 'oidc_cron_hook', array( $this, 'cleanupOldCodes' ) );
	}
	/**
	* Retrieve the user ID associated with a given auth code.
	 *
	 * @param string $code The auth code to lookup.
	 *
	 * @return int|null The user ID associated with the auth code, or null if not found.
	*/

	private function getUserIdByCode( $code ) {
		if ( empty( $code ) ) {
			return null;
		}

		$users = get_users(
			array(
				'number'       => 1,
				// Specifying blog_id does nothing for non-MultiSite installs. But for MultiSite installs, it allows you
				// to customize users of which site is supposed to be available for whatever sites
				// this plugin is meant to be activated on.
				'blog_id'      => apply_filters( 'oidc_auth_code_storage_blog_id', get_current_blog_id() ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'     => self::META_KEY_PREFIX . '_client_id_' . $code,
				// Using a meta_key EXISTS query is not slow, see https://github.com/WordPress/WordPress-Coding-Standards/issues/1871.
				'meta_compare' => 'EXISTS',
			)
		);

		if ( empty( $users ) ) {
			return null;
		}

		return absint( $users[0]->ID );
	}
	/**
	* Retrieve the authorization code for a given code.
	 *
	 * @param string $code The code to retrieve the authorization code for.
	 *
	 * @return array|null An array containing the authorization code data, or null if no code was found.
	*/

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
	/**
	* Sets the authorization code for a user
	 *
	 * @param string $code The authorization code
	 * @param string $client_id The client ID
	 * @param string $user_id The user ID
	 * @param string $redirect_uri The redirect URI
	 * @param int $expires The expiration time
	 * @param string|null $scope The scope
	 * @param string|null $id_token The ID token
	*/

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
	/**
	* Expire an authorization code
	 * 
	 * @param string $code The authorization code to expire
	 * 
	 * @return null
	*/

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
	/**
	* Uninstall function for the OIDC plugin.
	 *
	 * This function deletes all user meta data associated with the OIDC plugin.
	*/

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
