<?php
namespace OpenIDConnectServer;
use OAuth2;

class Rest {
	private $server;
	const NAMESPACE = 'openid-connect';
	public function __construct( $server ) {
		$this->server = $server;
		add_action( 'rest_api_init', array( $this, 'add_rest_routes' ) );
	}

	public function add_rest_routes() {
		register_rest_route(
			self::NAMESPACE,
			'token',
			array(
				'methods'             => 'POST', // MUST support POST only
				'callback'            => array( $this, 'token' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'authorize',
			array(
				'methods'             => 'GET,POST', // MUST support both GET and POST
				'callback'            => array( $this, 'authorize' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'userinfo',
			array(
				'methods'             => 'GET,POST',  // MUST support both GET and POST
				'callback'            => array( $this, 'userinfo' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function authorize() {
		$request = OAuth2\Request::createFromGlobals();
		$response = new OAuth2\Response();

		if ( ! $this->server->validateAuthorizeRequest( $request, $response ) ) {
			$response->send();
			exit;
		}

		// The initial OIDC request will come without a nonce, thus unauthenticated.
		if ( ! is_user_logged_in() || ! current_user_can( apply_filters( 'oidc_minimal_capability', 'edit_posts' ) ) ) {
			// This is handled in the main plugin file and will display a form asking the user to confirm.
			wp_safe_redirect( add_query_arg( $request->getAllQueryParameters(), home_url( '/openid-connect/authenticate' ) ) );
			exit;
		}

		if ( ! isset( $_POST['authorize'] ) || $_POST['authorize'] !== 'Authorize' ) {
			$response->send();
			exit;
		}

		$user = wp_get_current_user();
		if ( ! $user ) {
			$response->send();
			exit;
		}

		$this->server->handleAuthorizeRequest( $request, $response, true, $user->user_login );

		$response->send();
		exit;
	}

	public function token() {
		$this->server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
		exit;
	}

	public function userinfo() {
		$this->server->handleUserInfoRequest(OAuth2\Request::createFromGlobals())->send();
		exit;
	}
}
