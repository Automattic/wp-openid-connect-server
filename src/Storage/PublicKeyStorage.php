<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\Storage\PublicKeyInterface;

class PublicKeyStorage implements PublicKeyInterface {
	private string $public_key;
	private string $private_key;
	/**
	* Constructor for the class
	 * 
	 * @param string $public_key  The public key
	 * @param string $private_key The private key
	*/

	public function __construct( string $public_key, string $private_key ) {
		$this->public_key  = $public_key;
		$this->private_key = $private_key;
	}
	/**
	* Get the public key
	 *
	 * @param int|null $client_id The ID of the client
	 *
	 * @return string The public key
	*/

	public function getPublicKey( $client_id = null ) {
		return $this->public_key;
	}
	/**
	* Get the private key
	 *
	 * @param int|null $client_id The client id
	 *
	 * @return string The private key
	*/

	public function getPrivateKey( $client_id = null ) {
		return $this->private_key;
	}
	/**
	* Gets the encryption algorithm for the given client ID
	 *
	 * @param int $client_id The ID of the client
	 *
	 * @return string The encryption algorithm to use
	*/

	public function getEncryptionAlgorithm( $client_id = null ) {
		return 'RS256';
	}
}
