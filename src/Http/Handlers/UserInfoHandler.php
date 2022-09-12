<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Overrides\Server as OAuth2Server;

class UserInfoHandler extends RequestHandler {
	private OAuth2Server $server;

	public function __construct( OAuth2Server $server ) {
		$this->server = $server;
	}

	public function handle( Request $request, Response $response ): Response {
		return $this->server->handleUserInfoRequest( $request );
	}
}
