<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Server as OAuth2Server;
use OpenIDConnectServer\Http\RequestHandler;

class UserInfoHandler extends RequestHandler {
	private OAuth2Server $server;

	public function __construct( OAuth2Server $server ) {
		$this->server = $server;
	}

	public function handle( Request $request, Response $response ): Response {
		// prevent caching plugins from caching this page.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		$response = $this->server->handleUserInfoRequest( $request, $response );
		$response->addHttpHeaders(
			array(
				'Cache-Control' => 'no-store',
				'Pragma'        => 'no-cache',
			)
		);

		return $response;
	}
}
