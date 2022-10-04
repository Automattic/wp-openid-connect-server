<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\OpenID\Storage\AuthorizationCodeInterface;

class AuthorizationCodeStorage implements AuthorizationCodeInterface {
	const META_KEY_PREFIX = 'oidc';

	private static array $authorization_code_data = array(
		'code'         => 'string', // authorization code.
		'client_id'    => 'string', // client identifier.
		'redirect_uri' => 'string', // redirect URI.
		'expires'      => 'int',    // expires as unix timestamp.
		'scope'        => 'string', // scope as space-separated string.
		'id_token'     => 'string', // The OpenID Connect id_token.
	);

	public function getAuthorizationCode( $code ) {
		if ( empty( $code ) ) {
			return null;
		}

		$users = get_users(
			array(
				'meta_key'   => self::META_KEY_PREFIX . '_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( empty( $users ) ) {
			return null;
		}

		$user    = $users[0];
		$user_id = absint( $user->ID );

		$authorization_code = array( 'user_id' => $user->user_login );
		foreach ( array_keys( self::$authorization_code_data ) as $key ) {
			$meta_key                   = self::META_KEY_PREFIX . '_' . $key;
			$authorization_code[ $key ] = get_user_meta( $user_id, $meta_key, true );
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
				$meta_key = self::META_KEY_PREFIX . '_' . $key;
				update_user_meta( $user->ID, $meta_key, $value );
			}
		}
	}

	public function expireAuthorizationCode( $code ) {
		if ( empty( $code ) ) {
			return;
		}

		$users = get_users(
			array(
				'meta_key'   => self::META_KEY_PREFIX . '_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
			)
		);

		if ( empty( $users ) ) {
			return;
		}

		$user_id = absint( $users[0] );

		foreach ( array_keys( self::$authorization_code_data ) as $meta_key ) {
			delete_user_meta( $user_id, $meta_key );
		}
	}
}
