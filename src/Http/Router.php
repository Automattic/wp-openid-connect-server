<?php

namespace OpenIDConnectServer\Http;

use OAuth2\Request;
use OAuth2\Response;

class Router {
	private const PREFIX = 'openid-connect';

	private array $routes = array();

	private array $rest_routes = array();
	/**
	* Creates a URL from a given route.
	 *
	 * @param string $route The route to create the URL from.
	 *
	 * @return string The URL created from the given route.
	*/

	public static function make_url( string $route = '' ): string {
		return home_url( "/$route" );
	}
	/**
	* Constructs a REST URL for the given route.
	 *
	 * @param string $route The route to construct the URL for.
	 *
	 * @return string The constructed REST URL.
	*/

	public static function make_rest_url( string $route ): string {
		return rest_url( self::PREFIX . "/$route" );
	}
	/**
	* Constructor
	 * 
	 * @since 1.0.0
	 * 
	 * @access public
	 * 
	 * @return void
	*/

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
	}
	/**
	* Add a route to the router
	 *
	 * @param string $route The route to add
	 * @param RequestHandler $handler The request handler for the route
	*/

	public function add_route( string $route, RequestHandler $handler ) {
		if ( isset( $this->rest_routes[ $route ] ) ) {
			return;
		}

		$this->routes[ $route ] = $handler;
	}
	/**
	* Add a new REST route
	 * 
	 * @param string $route The route to add
	 * @param RequestHandler $handler The handler for the route
	 * @param array $methods The HTTP methods to allow for the route (defaults to GET)
	*/

	public function add_rest_route( string $route, RequestHandler $handler, array $methods = array( 'GET' ), array $args = array() ) {
		$route_with_prefix = self::PREFIX . "/$route";
		if ( isset( $this->rest_routes[ $route_with_prefix ] ) ) {
			return;
		}

		$this->rest_routes[ $route_with_prefix ] = $handler;

		add_action(
			'rest_api_init',
			function () use ( $route, $methods, $args ) {
				register_rest_route(
					self::PREFIX,
					$route,
					array(
						'methods'             => $methods,
						'permission_callback' => '__return_true',
						'callback'            => array( $this, 'handle_rest_request' ),
						'args'                => $args,
					)
				);
			}
		);
	}
	/**
	* Get the current route of the request
	 *
	 * @return string The current route of the request
	*/

	private function get_current_route(): string {
		$wp_url        = get_site_url();
		$installed_dir = wp_parse_url( $wp_url, PHP_URL_PATH );

		// Requested URI relative to WP install.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$uri = str_replace( $installed_dir, '', $uri );

		$route = strtok( $uri, '?' );

		return trim( $route, '/' );
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

		$route = $this->get_current_route();

		if ( ! isset( $this->routes[ $route ] ) ) {
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

		if ( ! isset( $this->rest_routes[ $route ] ) ) {
			$response = new Response();
			$response->setStatusCode( 404 );
			$response->send();
			exit;
		}

		$handler = $this->rest_routes[ $route ];
		$this->do_handle_request( $handler );
	}
	/**
	* Handles a request and sends a response
	 *
	 * @param RequestHandler $handler The request handler
	 *
	 * @return void
	*/

	private function do_handle_request( RequestHandler $handler ) {
		$request  = Request::createFromGlobals();
		$response = new Response();
		$response = $handler->handle( $request, $response );
		$response->send();
		exit();
	}
}
