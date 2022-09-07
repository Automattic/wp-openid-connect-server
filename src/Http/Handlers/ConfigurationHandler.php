<?php

namespace OpenIDConnectServer\Http\Handlers;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\RequestHandler;
use OpenIDConnectServer\Http\Router;

class ConfigurationHandler extends RequestHandler {
	public function handle( Request $request, Response $response ): Response {
		$response->addHttpHeaders(
			array(
				'Content-type'                => 'application/json',
				'Access-Control-Allow-Origin' => '*',
			)
		);

		$response->addParameters( $this->configuration() );

		return $response;
	}

	private function configuration(): array {
		return array(
			'issuer'                                => Router::make_url(),
			'jwks_uri'                              => Router::make_url( '.well-known/jwks.json' ),
			'authorization_endpoint'                => Router::make_rest_url( 'authorize' ),
			'token_endpoint'                        => Router::make_rest_url( 'token' ),
			'userinfo_endpoint'                     => Router::make_rest_url( 'userinfo' ),
			'scopes_supported'                      => array( 'openid', 'profile' ),
			'response_types_supported'              => array( 'code' ),
			'id_token_signing_alg_values_supported' => array( 'RS256' ),
		);
	}
}
