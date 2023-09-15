<?php

// phpcs:ignoreFile

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Storage\AuthorizationCodeStorage;

class TestHandler extends RequestHandler {
	private AuthorizationCodeStorage $storage;

	public function __construct() {
		$this->storage = new AuthorizationCodeStorage();
	}

	private function test() {
		$client_id    = '1234';
		$redirect_uri = 'https//example.org';
		$expires      = 1000;

		$this->storage->setAuthorizationCode( 'foo', $client_id, 'admin', $redirect_uri, $expires );
		$this->storage->setAuthorizationCode( 'bar', $client_id, 'test1', $redirect_uri, $expires );

		var_dump( $this->storage->getAuthorizationCode( 'foo' ) );
		var_dump( $this->storage->getAuthorizationCode( 'bar' ) );
		exit;
	}

	public function handle( Request $request, Response $response ): Response {
		$this->test();
		$response->setStatusCode( 200 );
		$response->setParameter( 'foo', 'bar' );
		return $response;
	}
}
