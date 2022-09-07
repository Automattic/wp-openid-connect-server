<?php

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

namespace OpenIDConnectServer\Http;

use OAuth2\Request;
use OAuth2\Response;

class Router {
	private const PREFIX = 'openid-connect';

	private array $routes = array();

	public static function makeRestUrl( $route ): string {
		return rest_url( Router::PREFIX . "/$route" );
	}

	public function addRoute( string $route, RequestHandler $handler ) {
		if ( array_key_exists( $route, $this->routes ) ) {
			return;
		}

		$this->routes[ $route ] = $handler;
	}

	public function addRestRoute( string $route, RequestHandler $handler, array $methods = array( 'GET' ) ) {
		$this->addRoute( $route, $handler );

		add_action(
			'rest_api_init',
			function () use ( $route, $methods ) {
				register_rest_route(
					self::PREFIX,
					$route,
					array(
						'methods'             => $methods,
						'permission_callback' => '__return_true',
						'callback'            => array( $this, 'handleRestRequest' ),
					)
				);
			}
		);
	}

	public function handleRestRequest( $wp_request ) {
		$request  = Request::createFromGlobals();
		$response = new Response();

		$route = $this->getRoute( $wp_request );
		if ( ! array_key_exists( $route, $this->routes ) ) {
			$response->setStatusCode( 404 );

			return $response;
		}

		/** @var RequestHandler $handler */
		$handler = $this->routes[ $route ];

		$response = $handler->handle( $request, $response );
		$response->send();
		exit();
	}

	private function getRoute( $wp_request ): string {
		$route_with_prefix = $wp_request->get_route();

		// Remove prefix.
		$route = str_replace( self::PREFIX, '', $route_with_prefix );

		// Remove leading slashes.
		return ltrim( $route, '/' );
	}
}
