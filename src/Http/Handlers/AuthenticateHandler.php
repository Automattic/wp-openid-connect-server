<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Http\Router;
use OpenIDConnectServer\Storage\ConsentStorage;

class AuthenticateHandler extends RequestHandler {
	private ConsentStorage $consent_storage;
	private array $clients;

	public function __construct( ConsentStorage $consent_storage, array $clients ) {
		$this->clients         = $clients;
		$this->consent_storage = $consent_storage;
	}

	public function handle( Request $request, Response $response ): Response {
		$client_name = $this->get_client_name( $request );
		if ( empty( $client_name ) ) {
			$response->setStatusCode( 404 );

			return $response;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		if ( $this->consent_storage->needs_consent( get_current_user_id() ) ) {
			include __DIR__ . '/../../Template/Authorize.php';
		} else {
			// rebuild request with all parameters and send to authorize endpoint.
			wp_safe_redirect(
				add_query_arg(
					array_merge(
						array( '_wpnonce' => wp_create_nonce( 'wp_rest' ) ),
						$request->getAllQueryParameters()
					),
					Router::make_rest_url( 'authorize' )
				)
			);
		}

		// TODO: return response instead of exiting.
		exit;
	}

	/**
	 * TODO: Remove this function in favour of ClientCredentialsStorage?
	 */
	private function get_client_name( Request $request ): string {
		$client_id = $request->query( 'client_id' );

		if ( ! isset( $this->clients[ $client_id ] ) ) {
			return '';
		}

		$client = $this->clients[ $client_id ];

		if ( empty( $client['name'] ) ) {
			return '';
		}

		return $client['name'];
	}
}
