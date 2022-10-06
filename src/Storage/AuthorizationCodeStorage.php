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

	private function getCodeMeta( $code ) {
		global $wpdb;

		if ( empty( $code ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery phpcs: WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE %s;",
				'%' . $wpdb->esc_like( $code ),
			)
		);

		if ( empty( $meta_row ) ) {
			return null;
		}

		return array(
			'user_id'   => absint( $meta_row->user_id ),
			'client_id' => str_replace( 'oidc_', '', str_replace( '_code_' . $code, '', $meta_row->meta_key ) ),
		);
	}

	public function getAuthorizationCode( $code ) {
		$meta = $this->getCodeMeta( $code );
		if ( empty( $meta ) ) {
			return null;
		}

		$user_id   = $meta['user_id'];
		$client_id = $meta['client_id'];

		$authorization_code = array(
			'user_id'   => $user_id,
			'client_id' => $client_id,
			'code'      => $code,
		);
		foreach ( array_keys( self::$authorization_code_data ) as $key ) {
			if ( 'code' === $key || 'client_id' === $key ) {
				continue;
			}

			$meta_key                   = self::META_KEY_PREFIX . '_' . $client_id . '_' . $key;
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

				if ( 'code' === $key ) {
					// store code in meta_key itself, so that SQL query is efficient since meta_key has index defined on it.
					$meta_key = self::META_KEY_PREFIX . '_' . $client_id . '_' . $key . '_' . $code;
				} else {
					$meta_key = self::META_KEY_PREFIX . '_' . $client_id . '_' . $key;
				}

				update_user_meta( $user->ID, $meta_key, $value );
			}
		}
	}

	public function expireAuthorizationCode( $code ) {
		$meta = $this->getCodeMeta( $code );
		if ( empty( $meta ) ) {
			return null;
		}

		$user_id   = $meta['user_id'];
		$client_id = $meta['client_id'];

		foreach ( array_keys( self::$authorization_code_data ) as $key ) {
			if ( 'code' === $key ) {
				$meta_key = self::META_KEY_PREFIX . '_' . $client_id . '_' . $key . '_' . $code;
			} else {
				$meta_key = self::META_KEY_PREFIX . '_' . $client_id . '_' . $key;
			}

			delete_user_meta( $user_id, $meta_key );
		}
	}
}
