<?php
namespace OpenIDConnectServer;
use OAuth2;

class OAuth2_Storage implements OAuth2\Storage\ClientInterface, OAuth2\Storage\ClientCredentialsInterface, OAuth2\OpenID\Storage\AuthorizationCodeInterface, OAuth2\OpenID\Storage\UserClaimsInterface {
	const TAXONOMY = 'oicd-authorization-code';
	private $authorization_code_data = array(
		'code' => 'string',         // authorization code.
		'client_id' => 'string',    // client identifier.
		'user_id' => 'int',         // The WordPress user id.
		'redirect_uri' => 'string', // redirect URI.
		'expires' => 'int',         // expires as unix timestamp.
		'scope' => 'string',        // scope as space-separated string.
		'id_token' => 'string',     // The OpenID Connect id_token.
	);

	private static $clients;

	public function __construct() {
		self::$clients = function_exists( '\oidc_clients' ) ? \oidc_clients() : array();

		// Store the authorization codes in a taxonomy.
		register_taxonomy( self::TAXONOMY, null );
		foreach ( $this->authorization_code_data as $key => $type ) {
			register_term_meta(
				self::TAXONOMY,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'sanitize_callback' => array( __CLASS__, 'validate_' . $key ),
				)
			);
		}
	}

	public static function validate_string_length( $string, $length ) {
		return substr( $string, 0, $length );
	}

	public static function validate_code( $code ) {
		return self::validate_string_length( $code, 40 );
	}

	public static function validate_client_id( $client_id ) {
		return self::validate_string_length( $client_id, 200 );
	}

	public static function validate_redirect_uri( $redirect_uri ) {
		return self::validate_string_length( $redirect_uri, 2000 );
	}

	public static function validate_scope( $scope ) {
		return self::validate_string_length( $scope, 100 );
	}

	public static function validate_id_token( $id_token ) {
		return self::validate_string_length( $id_token, 2000 );
	}

	public static function validate_user_id( $user_id ) {
		return intval( $user_id );
	}

	public static function validate_expires( $expires ) {
		return intval( $expires );
	}

	public static function getClientName( $client_id ) {
		if ( empty( self::$clients[$client_id]['name'] ) ) {
			return __( 'Unknown Client', 'wp-openid-connect-server' );
		}

		return self::$clients[$client_id]['name'];
	}

	/**
	 * Fetch authorization code data (probably the most common grant type).
	 *
	 * Retrieve the stored data for the given authorization code.
	 *
	 * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
	 *
	 * @param $code
	 * Authorization code to be check with.
	 *
	 * @return
	 * An associative array as below, and NULL if the code is invalid
	 * @code
	 * return array(
	 *     "client_id"    => CLIENT_ID,      // REQUIRED Stored client identifier
	 *     "user_id"      => USER_ID,        // REQUIRED Stored user identifier
	 *     "expires"      => EXPIRES,        // REQUIRED Stored expiration in unix timestamp
	 *     "redirect_uri" => REDIRECT_URI,   // REQUIRED Stored redirect URI
	 *     "scope"        => SCOPE,          // OPTIONAL Stored scope values in space-separated string
	 * );
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1
	 *
	 * @ingroup oauth2_section_4
	 */
	public function getAuthorizationCode( $code ) {
		$term = get_term_by( 'slug', $code, self::TAXONOMY );

		if ( $term ) {
			$authorization_code = array();
			foreach ( array_keys( $this->authorization_code_data ) as $key ) {
				$authorization_code[$key] = get_term_meta( $term->term_id, $key, true );
			}

			return $authorization_code;
		}

		return null;
	}

	/**
	 * Take the provided authorization code values and store them somewhere.
	 *
	 * This function should be the storage counterpart to getAuthCode().
	 *
	 * If storage fails for some reason, we're not currently checking for
	 * any sort of success/failure, so you should bail out of the script
	 * and provide a descriptive fail message.
	 *
	 * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
	 *
	 * @param string $code         - authorization code to be stored.
	 * @param mixed $client_id     - client identifier to be stored.
	 * @param mixed $user_id       - user identifier to be stored.
	 * @param string $redirect_uri - redirect URI(s) to be stored in a space-separated string.
	 * @param int    $expires      - expiration to be stored as a Unix timestamp.
	 * @param string $scope        - OPTIONAL scopes to be stored in space-separated string.
	 * @param string $id_token     - OPTIONAL the OpenID Connect id_token.
	 *
	 * @ingroup oauth2_section_4
	 */
	public function setAuthorizationCode( $code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null ) {
		if ( $code ) {
			$this->expireAuthorizationCode( $code );

			$term = wp_insert_term( $code, self::TAXONOMY );
			if ( is_wp_error( $term ) || ! isset( $term['term_id'] ) ) {
				status_header( 500 );
				exit;
			}

			foreach ( array(
				'client_id' => $client_id,
				'user_id' => $user_id,
				'redirect_uri' => $redirect_uri,
				'expires' => $expires,
				'scope' => $scope,
				'id_token' => $id_token,
				''
			) as $key => $value ) {
				add_term_meta(  $term['term_id'], $key, $value );
			}
		}
	}

	/**
	 * once an Authorization Code is used, it must be expired
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1.2
	 *
	 *    The client MUST NOT use the authorization code
	 *    more than once.  If an authorization code is used more than
	 *    once, the authorization server MUST deny the request and SHOULD
	 *    revoke (when possible) all tokens previously issued based on
	 *    that authorization code
	 *
	 */
	public function expireAuthorizationCode( $code ) {
		$term = get_term_by( 'slug', $code, self::TAXONOMY );

		if ( $term ) {
			wp_delete_term( $term->term_id, self::TAXONOMY );
		}
	}

	/**
	 * Get client details corresponding client_id.
	 *
	 * OAuth says we should store request URIs for each registered client.
	 * Implement this function to grab the stored URI for a given client id.
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 *
	 * @return array
	 *               Client details. The only mandatory key in the array is "redirect_uri".
	 *               This function MUST return FALSE if the given client does not exist or is
	 *               invalid. "redirect_uri" can be space-delimited to allow for multiple valid uris.
	 *               <code>
	 *               return array(
	 *               "redirect_uri" => REDIRECT_URI,      // REQUIRED redirect_uri registered for the client
	 *               "client_id"    => CLIENT_ID,         // OPTIONAL the client id
	 *               "grant_types"  => GRANT_TYPES,       // OPTIONAL an array of restricted grant types
	 *               "user_id"      => USER_ID,           // OPTIONAL the user identifier associated with this client
	 *               "scope"        => SCOPE,             // OPTIONAL the scopes allowed for this client
	 *               );
	 *               </code>
	 *
	 * @ingroup oauth2_section_4
	 */
	public function getClientDetails( $client_id ) {
		if ( isset( self::$clients[ $client_id ] ) ) {
			return array(
				'redirect_uri' => self::$clients[ $client_id ]['redirect_uri'],
				'client_id' => $client_id,
				'scope' => self::$clients[ $client_id ]['scope'],
			);
		}

		return false;
	}

	/**
	 * Get the scope associated with this client
	 *
	 * @return
	 * STRING the space-delineated scope list for the specified client_id
	 */
	public function getClientScope( $client_id ) {
		if ( isset( self::$clients[ $client_id ]['scope'] ) ) {
			return self::$clients[ $client_id ]['scope'];
		}

		return '';
	}

	/**
	 * Check restricted grant types of corresponding client identifier.
	 *
	 * If you want to restrict clients to certain grant types, override this
	 * function.
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 * @param $grant_type
	 * Grant type to be check with
	 *
	 * @return
	 * TRUE if the grant type is supported by this client identifier, and
	 * FALSE if it isn't.
	 *
	 * @ingroup oauth2_section_4
	 */
	public function checkRestrictedGrantType( $client_id, $grant_type ) {
		if ( isset( self::$clients[ $client_id ]['grant_types'] ) ) {
			return in_array( $grant_type, self::$clients[ $client_id ]['grant_types'] );
		}

		return false;
	}


	/**
	 * Return claims about the provided user id.
	 *
	 * Groups of claims are returned based on the requested scopes. No group
	 * is required, and no claim is required.
	 *
	 * @param mixed  $user_id - The id of the user for which claims should be returned.
	 * @param string $scope   - The requested scope.
	 * Scopes with matching claims: profile, email, address, phone.
	 *
	 * @return array - An array in the claim => value format.
	 *
	 * @see http://openid.net/specs/openid-connect-core-1_0.html#ScopeClaims
	 */
	public function getUserClaims( $user_id, $scope ) {
		$claims = array(
			// We expose the scope here so that it's in the token (unclear from the specs but the userinfo endpoint reads the scope from the token).
			'scope' => $scope,
		);
		if ( ! empty( $_REQUEST['nonce'] ) ) {
			$claims['nonce'] = $_REQUEST['nonce'];
		}

		foreach ( explode( ' ', $scope ) as $s ) {
			if ( $s === 'profile') {
				$user = \get_user_by( 'id', $user_id );
				if ( $user ) {
					foreach ( array(
						'username' => 'user_login',
						'given_name' => 'first_name',
						'family_name' => 'last_name',
						'nickname' => 'user_nicename',
					) as $key => $value ) {
						if ( $user->$value ) {
							$claims[ $key ] = $user->$value;
						}
					}
				}
			}
		}

		return $claims;
	}


	/**
	 * Make sure that the client credentials is valid.
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 * @param $client_secret
	 * (optional) If a secret is required, check that they've given the right one.
	 *
	 * @return
	 * TRUE if the client credentials are valid, and MUST return FALSE if it isn't.
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-3.1
	 *
	 * @ingroup oauth2_section_3
	 */
	public function checkClientCredentials( $client_id, $client_secret = null ) {
		if ( isset( self::$clients[ $client_id ] ) ) {
			if ( ! isset( self::$clients[ $client_id ]['secret'] ) ) {
				return true;
			}
			return $client_secret === self::$clients[ $client_id ]['secret'];
		}

		return false;
	}

	/**
	 * Determine if the client is a "public" client, and therefore
	 * does not require passing credentials for certain grant types
	 *
	 * @param $client_id
	 * Client identifier to be check with.
	 *
	 * @return
	 * TRUE if the client is public, and FALSE if it isn't.
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/rfc6749#section-2.3
	 * @see https://github.com/bshaffer/oauth2-server-php/issues/257
	 *
	 * @ingroup oauth2_section_2
	 */
	public function isPublicClient($client_id) {
		return isset( self::$clients[ $client_id ] ) && ! isset( self::$clients[ $client_id ]['secret'] );

	}
}
