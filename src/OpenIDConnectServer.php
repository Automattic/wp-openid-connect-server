<?php

namespace OpenIDConnectServer;

use OAuth2\Request;
use OpenIDConnectServer\Http\Handlers\TokenHandler;
use OpenIDConnectServer\Http\Router;
use OpenIDConnectServer\Overrides\Server;
use OpenIDConnectServer\Storage\AuthorizationCodeStorage;
use OpenIDConnectServer\Storage\ClientCredentialsStorage;
use OpenIDConnectServer\Storage\PublicKeyStorage;
use OpenIDConnectServer\Storage\UserClaimsStorage;
use function openssl_pkey_get_details;
use function openssl_pkey_get_public;

class OpenIDConnectServer {
	private $rest;
	private $router;
	private string $public_key;
	private array $clients;

	public function __construct( string $public_key, string $private_key, array $clients ) {
		$this->public_key = $public_key;
		$this->clients = $clients;

		$config = array(
			'use_jwt_access_tokens' => true,
			'use_openid_connect'    => true,
			'issuer'                => home_url( '/' ),
		);

		$server = new Server( new AuthorizationCodeStorage(), $config );
		$server->addStorage( new PublicKeyStorage( $public_key, $private_key ), 'public_key' );
		$server->addStorage( new ClientCredentialsStorage( $clients ), 'client_credentials' );
		$server->addStorage( new UserClaimsStorage(), 'user_claims' );

		// Add REST endpoints.
		$this->rest = new Rest( $server );

		$this->router = new Router( $server );
		$this->router->addRestRoute( 'token', TokenHandler::class, array( 'POST' ) );

		add_action( 'template_redirect', array( $this, 'jwks' ) );
		add_action( 'template_redirect', array( $this, 'openid_configuration' ) );
		add_action( 'template_redirect', array( $this, 'openid_authenticate' ) );
	}

	public function jwks() {
		if ( empty( $_SERVER['REQUEST_URI'] ) || '/.well-known/jwks.json' !== $_SERVER['REQUEST_URI'] ) {
			return;
		}
		status_header( 200 );
		header( 'Content-type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );

		$key_info = openssl_pkey_get_details( openssl_pkey_get_public( $this->public_key ) );

		echo wp_json_encode(
			array(
				'keys' =>
					array(
						array(
							'kty' => 'RSA',
							'use' => 'sig',
							'alg' => 'RS256',
							// phpcs:ignore
							'n'   => rtrim( strtr( base64_encode( $key_info['rsa']['n'] ), '+/', '-_' ), '=' ),
							// phpcs:ignore
							'e'   => rtrim( strtr( base64_encode( $key_info['rsa']['e'] ), '+/', '-_' ), '=' ),
						),
					),
			)
		);
		exit;
	}

	public function openid_configuration() {
		if ( empty( $_SERVER['REQUEST_URI'] ) || '/.well-known/openid-configuration' !== $_SERVER['REQUEST_URI'] ) {
			return;
		}
		status_header( 200 );
		header( 'Content-type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );
		echo wp_json_encode(
			array(
				'issuer'                                => home_url( '/' ),
				'authorization_endpoint'                => rest_url( 'openid-connect/authorize' ),
				'token_endpoint'                        => rest_url( 'openid-connect/token' ),
				'userinfo_endpoint'                     => rest_url( 'openid-connect/userinfo' ),
				'jwks_uri'                              => home_url( '/.well-known/jwks.json' ),
				'scopes_supported'                      => array( 'openid', 'profile' ),
				'response_types_supported'              => array( 'code' ),
				'id_token_signing_alg_values_supported' => array( 'RS256' ),
			)
		);
		exit;
	}

	public function openid_authenticate() {
		global $wp;

		if ( 'openid-connect/authenticate' !== $wp->request ) {
			return;
		}

		$request = Request::createFromGlobals();
		$client_name = $this->get_client_name( $request );

		if ( ! $client_name ) {
			return;

		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		if ( $this->rest->is_consent_needed() ) {
			define( 'OIDC_DISPLAY_AUTHORIZE', true );

			status_header( 200 );
			include __DIR__ . '/Template/Authorize.php';
		} else {
			// rebuild request with all parameters and send to authorize endpoint.
			$url = rest_url( Rest::NAMESPACE . '/authorize' );
			wp_safe_redirect(
				add_query_arg(
					array_merge(
						array( '_wpnonce' => wp_create_nonce( 'wp_rest' ) ),
						$request->getAllQueryParameters()
					),
					$url
				)
			);
		}
		exit;
	}

	// TODO: Remove this function in favour of ClientCredentialsStorage?
	private function get_client_name( Request $request ): string {
		$client_id = $request->query( 'client_id' );

		if ( ! array_key_exists( $client_id, $this->clients ) ) {
			return '';
		}

		$client = $this->clients[ $client_id ];

		if ( empty( $client['name'] ) ) {
			return '';
		}

		return $client['name'];
	}
}
