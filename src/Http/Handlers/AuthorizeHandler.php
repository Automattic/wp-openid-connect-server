<?php

// phpcs:disable WordPress.Security.NonceVerification.Missing

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server as OAuth2Server;
use OpenIDConnectServer\Http\RequestHandler;
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
		// Our dependency bshaffer's OAuth library currently has a bug where it doesn't pick up nonce correctly if it's a POST request to the Authorize endpoint.
		// Fix has been contributed upstream (https://github.com/bshaffer/oauth2-server-php/pull/1032) but it doesn't look it would be merged anytime soon based on recent activity.
		// Hence, as a temporary fix, we are copying over the nonce from parsed $_POST values to parsed $_GET values in $request object here.
		if ( isset( $request->request['nonce'] ) && ! isset( $request->query['nonce'] ) ) {
			$request->query['nonce'] = $request->request['nonce'];
		}

		if ( ! $this->server->validateAuthorizeRequest( $request, $response ) ) {
			return $response;
		}

		// The initial OIDC request will come without a nonce, thus unauthenticated.
		if ( ! is_user_logged_in() || ! current_user_can( apply_filters( 'oidc_minimal_capability', OIDC_DEFAULT_MINIMAL_CAPABILITY ) ) ) {
			// This is handled by a hook in wp-login.php which will display a form asking the user to consent.
			// TODO: Redirect with $response->setRedirect().
			wp_safe_redirect( add_query_arg( array_map( 'rawurlencode', array_merge( $request->getAllQueryParameters(), array( 'action' => 'openid-authenticate' ) ) ), wp_login_url() ) );
			exit;
		}

		$user = wp_get_current_user();

		$client_id = $request->query( 'client_id', $request->request( 'client_id' ) );
		if ( $this->consent_storage->needs_consent( $user->ID, $client_id ) ) {
			if ( ! isset( $_POST['authorize'] ) || __( 'Authorize', 'openid-connect-server' ) !== $_POST['authorize'] ) {
				$response->setError( 403, 'user_authorization_required', 'This application requires your consent.' );
				return $response;
			}

			$this->consent_storage->update_timestamp( $user->ID, $client_id );
		}

		return $this->server->handleAuthorizeRequest( $request, $response, true, $user->user_login );
	}
}
