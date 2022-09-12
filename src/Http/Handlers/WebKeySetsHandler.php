<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;

class WebKeySetsHandler extends RequestHandler {
	private string $public_key;

	public function __construct( string $public_key ) {
		$this->public_key = $public_key;
	}

	public function handle( Request $request, Response $response ): Response {
		$response->addHttpHeaders(
			array(
				'Content-type'                => 'application/json',
				'Access-Control-Allow-Origin' => '*',
			)
		);

		$response->addParameters( array( 'keys' => array( $this->key_info() ) ) );

		return $response;
	}

	private function key_info(): array {
		$key = openssl_pkey_get_details( openssl_pkey_get_public( $this->public_key ) );

		return array(
			'kty' => 'RSA',
			'use' => 'sig',
			'alg' => 'RS256',
			// phpcs:ignore
			'n'   => rtrim( strtr( base64_encode( $key['rsa']['n'] ), '+/', '-_' ), '=' ),
			// phpcs:ignore
			'e'   => rtrim( strtr( base64_encode( $key['rsa']['e'] ), '+/', '-_' ), '=' ),
		);
	}
}
