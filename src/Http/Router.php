<?php

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

namespace OpenIDConnectServer\Http;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Overrides\Server as OAuth2Server;

const PREFIX = 'openid-connect';

class Router {
	private array $routes = array();
	private OAuth2Server $server;

	public function __construct( Oauth2Server $server ) {
		$this->server = $server;
	}

	public function addRoute( string $route, string $handlerClass ) {
		if ( array_key_exists( $route, $this->routes ) ) {
			return;
		}

		$this->routes[ $route ] = $handlerClass;
	}

	public function addRestRoute( string $route, string $handlerClass, array $methods = array( 'GET' ) ) {
		$this->addRoute( $route, $handlerClass );

		add_action(
			'rest_api_init',
			function () use ( $route, $methods ) {
				register_rest_route(
					PREFIX,
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
		$handler = new $this->routes[ $route ]( $this->server );

		$response = $handler->handle( $request, $response );
		$response->send();
		exit();
	}

	private function getRoute( $wp_request ): string {
		$route_with_prefix = $wp_request->get_route();

		// Remove prefix.
		$route = str_replace( PREFIX, '', $route_with_prefix );

		// Remove leading slashes.
		return ltrim( $route, '/' );
	}
}
