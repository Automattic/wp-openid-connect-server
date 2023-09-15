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

		$this->storage->expireAuthorizationCode( 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa-foo' );
		$this->storage->expireAuthorizationCode( 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa-bar' );
		$this->storage->expireAuthorizationCode( 'foo' );
		$this->storage->expireAuthorizationCode( 'bar' );

		$this->storage->setAuthorizationCode( 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa-foo', $client_id, 'admin', $redirect_uri, $expires );
		$this->storage->setAuthorizationCode( 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa-bar', $client_id, 'test1', $redirect_uri, $expires );

		var_dump( $this->storage->getAuthorizationCode( 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa-foo' ) );
//		var_dump( $this->storage->getAuthorizationCode( 'foo' ) );
		exit;
	}

	public function handle( Request $request, Response $response ): Response {
		$this->test();
		$response->setStatusCode( 200 );
		$response->setParameter( 'foo', 'bar' );
		return $response;
	}
}
