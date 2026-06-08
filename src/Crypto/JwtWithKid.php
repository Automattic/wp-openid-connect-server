<?php

namespace OpenIDConnectServer\Crypto;

use OAuth2\Encryption\Jwt;

class JwtWithKid extends Jwt {
	private string $kid;

	public function __construct( string $kid ) {
		$this->kid = $kid;
	}

	protected function generateJwtHeader( $payload, $algorithm ) {
		return array(
			'typ' => 'JWT',
			'alg' => $algorithm,
			'kid' => $this->kid,
		);
	}
}
