<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\Storage\ClientCredentialsInterface;

class ClientCredentialsStorage implements ClientCredentialsInterface {
	private array $clients;

	public function __construct( array $clients ) {
		$this->clients = $clients;
	}

	public function getClientDetails( $client_id ) {
		if ( ! $this->has( $client_id ) ) {
			return false;
		}

		$client = $this->get( $client_id );

		return array(
			'client_id'    => $client_id,
			'redirect_uri' => $client['redirect_uri'],
			'scope'        => $client['scope'],
		);
	}

	public function getClientScope( $client_id ) {
		if ( ! $this->has( $client_id ) ) {
			return '';
		}

		$client = $this->get( $client_id );

		if ( ! isset( $client['scope'] ) ) {
			return '';
		}

		return $client['scope'];
	}

	public function checkRestrictedGrantType( $client_id, $grant_type ) {
		if ( ! $this->has( $client_id ) ) {
			return false;
		}

		$client = $this->get( $client_id );

		if ( ! isset( $client['grant_types'] ) ) {
			return false;
		}

		return in_array( $grant_type, $client['grant_types'], true );
	}

	public function checkClientCredentials( $client_id, $client_secret = null ) {
		if ( ! $this->has( $client_id ) ) {
			return false;
		}

		$client = $this->get( $client_id );

		if ( empty( $client['secret'] ) ) {
			return true;
		}

		return $client_secret === $client['secret'];
	}

	public function isPublicClient( $client_id ) {
		if ( ! $this->has( $client_id ) ) {
			return false;
		}

		$client = $this->get( $client_id );

		return empty( $client['secret'] );
	}

	private function get( $client_id ) {
		if ( ! $this->has( $client_id ) ) {
			return null;
		}

		return $this->clients[ $client_id ];
	}

	private function has( $client_id ): bool {
		return isset( $this->clients[ $client_id ] );
	}
}
