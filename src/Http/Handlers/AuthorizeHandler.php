<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Overrides\Server as OAuth2Server;
use OpenIDConnectServer\Storage\ConsentStorage;

const OIDC_DEFAULT_MINIMAL_CAPABILITY = 'edit_posts';

class AuthorizeHandler extends RequestHandler {
	private OAuth2Server $server;
	private ConsentStorage $consent_storage;

	public function __construct( OAuth2Server $server, ConsentStorage $consent_storage ) {
		$this->server          = $server;
		$this->consent_storage = $consent_storage;
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
		if ( $this->consent_storage->needs_consent( $user->ID ) ) {
			if ( ! isset( $_POST['authorize'] ) || 'Authorize' !== $_POST['authorize'] ) {
				$response->send();
				exit;
			}

			$this->consent_storage->update_timestamp( get_current_user_id() );
		}

		return $this->server->handleAuthorizeRequest( $request, $response, true, $user->user_login );
	}
}
