<?php

namespace OpenIDConnectServer;

use OAuth2\Request;
use OAuth2\Response;

const OIDC_DEFAULT_MINIMAL_CAPABILITY = 'edit_posts';

class Rest {
	private $server;

	const NAMESPACE = 'openid-connect';

	const STICKY_CONSENT_DURATION = 7 * DAY_IN_SECONDS;

	public function __construct( $server ) {
		$this->server = $server;
		add_action( 'rest_api_init', array( $this, 'add_rest_routes' ) );
	}

	public function add_rest_routes() {
		register_rest_route(
			self::NAMESPACE,
			'authorize',
			array(
				'methods'             => 'GET,POST', // MUST support both GET and POST.
				'callback'            => array( $this, 'authorize' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'userinfo',
			array(
				'methods'             => 'GET,POST',  // MUST support both GET and POST.
				'callback'            => array( $this, 'userinfo' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function authorize() {
		$request  = Request::createFromGlobals();
		$response = new Response();

		if ( ! $this->server->validateAuthorizeRequest( $request, $response ) ) {
			$response->send();
			exit;
		}

		// The initial OIDC request will come without a nonce, thus unauthenticated.
		if ( ! is_user_logged_in() || ! current_user_can( apply_filters( 'oidc_minimal_capability', OIDC_DEFAULT_MINIMAL_CAPABILITY ) ) ) {
			// This is handled in the main plugin file and will display a form asking the user to confirm.
			wp_safe_redirect( add_query_arg( $request->getAllQueryParameters(), home_url( '/openid-connect/authenticate' ) ) );
			exit;
		}

		$user = wp_get_current_user();
		if ( $this->is_consent_needed() ) {
			if ( ! isset( $_POST['authorize'] ) || 'Authorize' !== $_POST['authorize'] ) {
				$response->send();
				exit;
			}

			$this->update_consent_timestamp();
		}

		$this->server->handleAuthorizeRequest( $request, $response, true, $user->user_login );

		$response->send();
		exit;
	}

	public function userinfo() {
		$this->server->handleUserInfoRequest( Request::createFromGlobals() )->send();
		exit;
	}

	public function is_consent_needed(): bool {
		$current_user_id   = get_current_user_id();
		$consent_timestamp = absint( get_user_meta( $current_user_id, 'oidc_consent_timestamp', true ) );

		$past_consent_expiry = time() > ( $consent_timestamp + ( self::STICKY_CONSENT_DURATION ) );

		return empty( $consent_timestamp ) || $past_consent_expiry;
	}

	public function update_consent_timestamp() {
		update_user_meta( get_current_user_id(), 'oidc_consent_timestamp', time() );
	}
}
