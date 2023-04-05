<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server as OAuth2Server;
use OpenIDConnectServer\Http\RequestHandler;

class UserInfoHandler extends RequestHandler {
	private OAuth2Server $server;
	/**
	* Constructor
	 * 
	 * @param OAuth2Server $server The OAuth2 server object
	*/

	public function __construct( OAuth2Server $server ) {
		$this->server = $server;
	}
	/**
	* Handles a Request and returns a Response
	 *
	 * @param Request $request The Request object
	 * @param Response $response The Response object
	 *
	 * @return Response The Response object
	*/

	public function handle( Request $request, Response $response ): Response {
		return $this->server->handleUserInfoRequest( $request );
	}
}
