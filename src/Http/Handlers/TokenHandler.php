<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server as OAuth2Server;
use OpenIDConnectServer\Http\RequestHandler;

class TokenHandler extends RequestHandler {
	private OAuth2Server $server;

	/**
	 * Constructor
	 *
	 * @param OAuth2Server $server The OAuth2 server instance
	 */

	public function __construct( OAuth2Server $server ) {
		$this->server = $server;
	}

	/**
	 * Handles a request and returns a response.
	 *
	 * @param Request  $request  The request to handle
	 * @param Response $response The response to return
	 *
	 * @return Response The response
	 */

	public function handle( Request $request, Response $response ): Response {
		return $this->server->handleTokenRequest( $request );
	}
}
