<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;

class WebKeySetsHandler extends RequestHandler {
	private string $public_key;
	/**
	* Constructor for the class
	 * 
	 * @param string $public_key The public key to be used
	*/

	public function __construct( string $public_key ) {
		$this->public_key = $public_key;
	}
	/**
	* Handle the Request and Response objects
	 *
	 * @param Request $request The Request object
	 * @param Response $response The Response object
	 *
	 * @return Response The modified Response object
	*/

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
	/**
	* Retrieve the key information
	 * 
	 * @return array An array containing the key information (kty, use, alg, n, e)
	*/

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
