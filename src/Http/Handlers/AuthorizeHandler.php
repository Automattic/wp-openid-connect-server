<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Overrides\Server as OAuth2Server;
use OpenIDConnectServer\Rest;

const OIDC_DEFAULT_MINIMAL_CAPABILITY = 'edit_posts';

class AuthorizeHandler extends RequestHandler {
	private OAuth2Server $server;
	private Rest $rest;

	public function __construct( OAuth2Server $server ) {
		$this->server = $server;
		$this->rest   = new Rest( $server );
	}

	public function handle( Request $request, Response $response ): Response {
		if ( ! $this->server->validateAuthorizeRequest( $request, $response ) ) {
			return $response;
		}

		// The initial OIDC request will come without a nonce, thus unauthenticated.
		if ( ! is_user_logged_in() || ! current_user_can( apply_filters( 'oidc_minimal_capability', OIDC_DEFAULT_MINIMAL_CAPABILITY ) ) ) {
			// This is handled in the main plugin file and will display a form asking the user to confirm.
			// TODO: Redirect with $response->setRedirect().
			wp_safe_redirect( add_query_arg( $request->getAllQueryParameters(), home_url( '/openid-connect/authenticate' ) ) );
			exit;
		}

		$user = wp_get_current_user();
		if ( $this->rest->is_consent_needed() ) {
			if ( ! isset( $_POST['authorize'] ) || 'Authorize' !== $_POST['authorize'] ) {
				$response->send();
				exit;
			}

			$this->rest->update_consent_timestamp();
		}

		return $this->server->handleAuthorizeRequest( $request, $response, true, $user->user_login );
	}
}
