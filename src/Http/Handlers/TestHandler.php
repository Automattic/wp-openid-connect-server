<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;

class TestHandler extends RequestHandler {
	public function handle( Request $request, Response $response ): Response {
		$response->setStatusCode( 200 );
		$response->setParameter( 'foo', 'bar' );
		return $response;
	}
}
