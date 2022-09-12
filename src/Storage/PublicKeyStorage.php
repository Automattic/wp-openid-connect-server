<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\Storage\PublicKeyInterface;

class PublicKeyStorage implements PublicKeyInterface {
	private string $public_key;
	private string $private_key;

	public function __construct( string $public_key, string $private_key ) {
		$this->public_key  = $public_key;
		$this->private_key = $private_key;
	}

	public function getPublicKey( $client_id = null ) {
		return $this->public_key;
	}

	public function getPrivateKey( $client_id = null ) {
		return $this->private_key;
	}

	public function getEncryptionAlgorithm( $client_id = null ) {
		return 'RS256';
	}
}
