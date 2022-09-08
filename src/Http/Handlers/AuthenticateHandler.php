<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Http\Router;
use OpenIDConnectServer\Storage\ConsentStorage;
use OpenIDConnectServer\Templating\Templating;

class AuthenticateHandler extends RequestHandler {
	private ConsentStorage $consent_storage;
	private Templating $templating;
	private array $clients;

	public function __construct( ConsentStorage $consent_storage, Templating $templating, array $clients ) {
		$this->consent_storage = $consent_storage;
		$this->templating      = $templating;
		$this->clients         = $clients;
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

		if ( ! $this->consent_storage->needs_consent( get_current_user_id() ) ) {
			$this->redirect( $request );
			// TODO: return response instead of exiting.
			exit;
		}

		$data = array(
			'user'            => wp_get_current_user(),
			'client_name'     => $client_name,
			'body_class_attr' => implode( ' ', array_diff( get_body_class(), array( 'error404' ) ) ),
			'cancel_url'      => Router::make_url(),
			'form_url'        => Router::make_rest_url( 'authorize' ),
			'form_fields'     => $request->getAllQueryParameters(),
		);

		$has_permission = current_user_can( apply_filters( 'oidc_minimal_capability', OIDC_DEFAULT_MINIMAL_CAPABILITY ) );
		if ( ! $has_permission ) {
			// phpcs:ignore
			echo $this->templating->render( 'authenticate/forbidden', $data );
			exit;
		}

		// phpcs:ignore
		echo $this->templating->render( 'authenticate/main', $data );

		// TODO: return response instead of exiting.
		exit;
	}

	private function redirect( Request $request ) {
		// Rebuild request with all parameters and send to authorize endpoint.
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
