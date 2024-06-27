<?php

namespace OpenIDConnectServer\Storage;

use OAuth2\Storage\ClientCredentialsInterface;

class ClientCredentialsStorage implements ClientCredentialsInterface {
	private array $clients;

	/**
	 * Constructor
	 *
	 * @param array $clients An array of clients
	 */

	public function __construct( array $clients ) {
		$this->clients = $clients;
	}

	/**
	 * Get the details of a client.
	 *
	 * @param int $client_id The ID of the client.
	 *
	 * @return array|false An array of client details or false if the client does not exist.
	 */

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

	/**
	 * Retrieve the scope of a client.
	 *
	 * @param int $client_id The ID of the client to retrieve the scope of.
	 *
	 * @return string The scope of the client, or an empty string if the client does not exist or does not have a scope.
	 */

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

	/**
	 * Checks if a given grant type is restricted for a given client
	 *
	 * @param string $client_id  The ID of the client
	 * @param string $grant_type The grant type to check
	 *
	 * @return bool True if the grant type is restricted for the client, false otherwise
	 */

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

	/**
	 * Checks the client credentials.
	 *
	 * @param string      $client_id     The client ID.
	 * @param string|null $client_secret The client secret (optional).
	 *
	 * @return bool True if the credentials are valid, false otherwise.
	 */

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

	/**
	 * Checks if the client is public or not.
	 *
	 * @param int $client_id The ID of the client to check.
	 *
	 * @return bool True if the client is public, false otherwise.
	 */

	public function isPublicClient( $client_id ) {
		if ( ! $this->has( $client_id ) ) {
			return false;
		}

		$client = $this->get( $client_id );

		return empty( $client['secret'] );
	}

	/**
	 * Retrieve a client by ID.
	 *
	 * @param int $client_id The ID of the client to retrieve.
	 *
	 * @return mixed|null The client object, or null if not found.
	 */

	private function get( $client_id ) {
		if ( ! $this->has( $client_id ) ) {
			return null;
		}

		return $this->clients[ $client_id ];
	}

	/**
	 * Checks if the given client ID exists in the clients array.
	 *
	 * @param int $client_id The ID of the client to check for.
	 *
	 * @return bool True if the client exists, false otherwise.
	 */

	private function has( $client_id ): bool {
		return isset( $this->clients[ $client_id ] );
	}
}
