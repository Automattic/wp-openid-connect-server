<?php

namespace OpenIDConnectServer\Crypto;

use RuntimeException;

class PublicKeyJwk {
	public static function from_public_key( string $public_key ): array {
		$key_resource = openssl_pkey_get_public( $public_key );

		if ( false === $key_resource ) {
			throw new RuntimeException( 'Unable to parse configured public key.' );
		}

		$key = openssl_pkey_get_details( $key_resource );

		if ( ! is_array( $key ) || empty( $key['rsa']['n'] ) || empty( $key['rsa']['e'] ) ) {
			throw new RuntimeException( 'Configured public key is not a valid RSA key.' );
		}

		$jwk = array(
			'kty' => 'RSA',
			'use' => 'sig',
			'alg' => 'RS256',
			'n'   => self::base64url_encode( $key['rsa']['n'] ),
			'e'   => self::base64url_encode( $key['rsa']['e'] ),
		);

		$jwk['kid'] = self::thumbprint( $jwk );

		return $jwk;
	}

	private static function thumbprint( array $jwk ): string {
		$thumbprint_payload = sprintf(
			'{"e":"%s","kty":"%s","n":"%s"}',
			$jwk['e'],
			$jwk['kty'],
			$jwk['n']
		);

		return self::base64url_encode( hash( 'sha256', $thumbprint_payload, true ) );
	}

	private static function base64url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}
}
