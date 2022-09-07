<?php

namespace OpenIDConnectServer\Http;

use OAuth2\Request;
use OAuth2\Response;

class Router {
	private const PREFIX = 'openid-connect';

	private array $rest_routes = array();

	public static function make_rest_url( $route ): string {
		return rest_url( self::PREFIX . "/$route" );
	}

	public function add_rest_route( string $route, RequestHandler $handler, array $methods = array( 'GET' ) ) {
		$route_with_prefix = self::PREFIX . "/$route";
		if ( array_key_exists( $route_with_prefix, $this->rest_routes ) ) {
			return;
		}

		$this->rest_routes[ $route_with_prefix ] = $handler;

		add_action(
			'rest_api_init',
			function () use ( $route, $methods ) {
				register_rest_route(
					self::PREFIX,
					$route,
					array(
						'methods'             => $methods,
						'permission_callback' => '__return_true',
						'callback'            => array( $this, 'handle_rest_request' ),
					)
				);
			}
		);
	}

	/**
	 * This method is meant for internal use in this class only.
	 * It must not be used elsewhere.
	 * It's only public since it's used as a callback.
	 */
	public function handle_rest_request( $wp_request ) {
		$request  = Request::createFromGlobals();
		$response = new Response();

		$route = $wp_request->get_route();
		// Remove leading slashes.
		$route = ltrim( $route, '/' );

		if ( ! array_key_exists( $route, $this->rest_routes ) ) {
			$response->setStatusCode( 404 );
			$response->send();
			exit;
		}

		/** @var RequestHandler $handler */
		$handler = $this->rest_routes[ $route ];

		$response = $handler->handle( $request, $response );
		$response->send();
		exit();
	}
}
