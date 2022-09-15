<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\OpenID\Storage\AuthorizationCodeInterface;

class AuthorizationCodeStorage implements AuthorizationCodeInterface {
	const TAXONOMY = 'oidc-authorization-code';

	private static array $authorization_code_data = array(
		'code'         => 'string', // authorization code.
		'client_id'    => 'string', // client identifier.
		'user_login'   => 'string', // The WordPress user id.
		'redirect_uri' => 'string', // redirect URI.
		'expires'      => 'int',    // expires as unix timestamp.
		'scope'        => 'string', // scope as space-separated string.
		'id_token'     => 'string', // The OpenID Connect id_token.
	);

	public function __construct() {
		// Store the authorization codes in a taxonomy.
		register_taxonomy( self::TAXONOMY, null );
		foreach ( self::$authorization_code_data as $key => $type ) {
			register_term_meta(
				self::TAXONOMY,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'sanitize_callback' => array( __CLASS__, 'sanitize_' . $key ),
				)
			);
		}
	}

	public static function sanitize_string_length( $string, $length ) {
		return substr( $string, 0, $length );
	}

	public static function sanitize_code( $code ) {
		return self::sanitize_string_length( $code, 40 );
	}

	public static function sanitize_client_id( $client_id ) {
		return self::sanitize_string_length( $client_id, 200 );
	}

	public static function sanitize_redirect_uri( $redirect_uri ) {
		return self::sanitize_string_length( $redirect_uri, 2000 );
	}

	public static function sanitize_scope( $scope ) {
		return self::sanitize_string_length( $scope, 100 );
	}

	public static function sanitize_id_token( $id_token ) {
		return self::sanitize_string_length( $id_token, 2000 );
	}

	public static function sanitize_user_login( $user_login ) {
		return self::sanitize_string_length( $user_login, 60 );
	}

	public static function sanitize_expires( $expires ) {
		return intval( $expires );
	}

	public function getAuthorizationCode( $code ) {
		$term = get_term_by( 'slug', $code, self::TAXONOMY );

		if ( $term ) {
			$authorization_code = array();
			foreach (
				array(
					'client_id'    => 'client_id',
					'user_id'      => 'user_login',
					'expires'      => 'expires',
					'redirect_uri' => 'redirect_uri',
					'scope'        => 'scope',
					'id_token'     => 'id_token',
				) as $key => $meta_key
			) {
				$authorization_code[ $key ] = get_term_meta( $term->term_id, $meta_key, true );
			}

			return $authorization_code;
		}

		return null;
	}

	public function setAuthorizationCode( $code, $client_id, $user_login, $redirect_uri, $expires, $scope = null, $id_token = null ) {
		if ( $code ) {
			$this->expireAuthorizationCode( $code );

			$term = wp_insert_term( $code, self::TAXONOMY );
			if ( is_wp_error( $term ) || ! isset( $term['term_id'] ) ) {
				status_header( 500 );
				exit;
			}

			foreach (
				array(
					'client_id'    => $client_id,
					'user_login'   => $user_login,
					'redirect_uri' => $redirect_uri,
					'expires'      => $expires,
					'scope'        => $scope,
					'id_token'     => $id_token,
				) as $key => $value
			) {
				add_term_meta( $term['term_id'], $key, $value );
			}
		}
	}

	public function expireAuthorizationCode( $code ) {
		$term = get_term_by( 'slug', $code, self::TAXONOMY );

		if ( $term ) {
			wp_delete_term( $term->term_id, self::TAXONOMY );
		}
	}
}
