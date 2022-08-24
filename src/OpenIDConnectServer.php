<?php
namespace OpenIDConnectServer;
use OAuth2;

class OpenIDConnectServer {
	public function __construct() {
		add_filter( 'site_status_tests', array( __NAMESPACE__ . '\SiteStatusTests', 'register_site_status_tests' ) );

		// Please follow the instructions in the readme for defining these.
		$public_key = defined( 'OIDC_PUBLIC_KEY' ) ? OIDC_PUBLIC_KEY : false;
		if ( ! $public_key ) {
			return false;
		}
		$private_key = defined( 'OIDC_PRIVATE_KEY' ) ? OIDC_PRIVATE_KEY : false;
		if ( ! $private_key ) {
			return false;
		}

		$config = array(
			'use_jwt_access_tokens' => true,
			'use_openid_connect' => true,
			'issuer' => home_url( '/' ),
		);

		$server = new OAuth2\Server( new OAuth2_Storage(), $config );

		$server->addStorage(
			new OAuth2\Storage\Memory(
				array(
					'keys' => compact( 'private_key', 'public_key' ),
				)
			),
			'public_key'
		);

		// Add REST endpoints.
		new Rest( $server );

		add_action( 'template_redirect', array( $this, 'jwks' ) );
		add_action( 'template_redirect', array( $this, 'openid_configuration' ) );
		add_action( 'template_redirect', array( $this, 'openid_authenticate' ) );
	}


	public function jwks() {
		if ( $_SERVER['REQUEST_URI'] !== '/.well-known/jwks.json' ) {
			return;
		}
		if ( ! defined( 'OIDC_PUBLIC_KEY' ) || empty( OIDC_PUBLIC_KEY ) ) {
			return;
		}
		status_header( 200 );
		header( 'Content-type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );

		$key_info = \openssl_pkey_get_details( \openssl_pkey_get_public( OIDC_PUBLIC_KEY ) );

		echo json_encode(
			array(
				'keys' =>
				array(
				array(
					'kty' => 'RSA',
					'use' => 'sig',
					'alg' => 'RS256',
					'n' => rtrim( strtr( base64_encode( $key_info['rsa']['n'] ), '+/', '-_' ), '=' ),
					'e' => rtrim( strtr( base64_encode( $key_info['rsa']['e'] ), '+/', '-_' ), '=' ),
				)
				)
			)
		);
		exit;
	}

	public function openid_configuration() {
		if ( $_SERVER['REQUEST_URI'] !== '/.well-known/openid-configuration' ) {
			return;
		}
		status_header( 200 );
		header( 'Content-type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( array(
			'issuer' => home_url( '/' ),
			'authorization_endpoint' => rest_url( 'openid-connect/authorize' ),
			'token_endpoint' => rest_url( 'openid-connect/token' ),
			'userinfo_endpoint' => rest_url( 'openid-connect/userinfo' ),
			'jwks_uri' => home_url( '/.well-known/jwks.json' ),
			'scopes_supported' => array( 'openid', 'profile' ),
			'response_types_supported' => array( 'code' ),
			'id_token_signing_alg_values_supported' => array( 'RS256' ),
		) );
		exit;
	}

	public function openid_authenticate() {
		global $wp;

		if ( $wp->request !== 'openid-connect/authenticate' ) {
			return;
		}

		$request = OAuth2\Request::createFromGlobals();
		if ( empty( $request->query( 'client_id' ) ) || ! OAuth2_Storage::getClientName( $request->query( 'client_id' ) ) ) {
			return;

		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		define( 'OIDC_DISPLAY_AUTHORIZE', true );

		status_header( 200 );
		include __DIR__ . '/Template/Authorize.php';
		exit;
	}
}
