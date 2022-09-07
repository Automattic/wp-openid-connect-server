<?php

namespace OpenIDConnectServer\Http;

use OAuth2\Request;
use OAuth2\Response;

class Router {
	private const PREFIX = 'openid-connect';

	private array $routes = array();

	private array $rest_routes = array();

	public static function make_rest_url( $route ): string {
		return rest_url( self::PREFIX . "/$route" );
	}

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
	}

	public function add_route( string $route, RequestHandler $handler ) {
		if ( array_key_exists( $route, $this->rest_routes ) ) {
			return;
		}

		$this->routes[ $route ] = $handler;
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
	public function handle_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$uri   = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$route = strtok( $uri, '?' );
		$route = ltrim( $route, '/' );

		if ( ! array_key_exists( $route, $this->routes ) ) {
			return;
		}

		$handler = $this->routes[ $route ];
		$this->do_handle_request( $handler );
	}

	/**
	 * This method is meant for internal use in this class only.
	 * It must not be used elsewhere.
	 * It's only public since it's used as a callback.
	 */
	public function handle_rest_request( $wp_request ) {
		$route = $wp_request->get_route();
		// Remove leading slashes.
		$route = ltrim( $route, '/' );

		if ( ! array_key_exists( $route, $this->rest_routes ) ) {
			$response = new Response();
			$response->setStatusCode( 404 );
			$response->send();
			exit;
		}

		$handler = $this->rest_routes[ $route ];
		$this->do_handle_request( $handler );
	}

	private function do_handle_request( RequestHandler $handler ) {
		$request  = Request::createFromGlobals();
		$response = new Response();
		$response = $handler->handle( $request, $response );
		$response->send();
		exit();
	}
}
