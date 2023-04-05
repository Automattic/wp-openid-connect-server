<?php

namespace OpenIDConnectServer\Storage;

const STICKY_CONSENT_DURATION = 7 * DAY_IN_SECONDS;

class ConsentStorage {
	const META_KEY_PREFIX = 'oidc_consent_timestamp_';
	/**
	* Get the meta key for a given client ID
	 *
	 * @param int $client_id The ID of the client
	 *
	 * @return string The meta key for the given client ID
	*/

	private function get_meta_key( $client_id ): string {
		return self::META_KEY_PREFIX . $client_id;
	}
	/**
	* Checks if a user needs to provide consent for a given client.
	 *
	 * @param int $user_id The ID of the user.
	 * @param int $client_id The ID of the client.
	 *
	 * @return bool True if the user needs to provide consent, false otherwise.
	*/

	public function needs_consent( $user_id, $client_id ): bool {
		$consent_timestamp = absint( get_user_meta( $user_id, $this->get_meta_key( $client_id ), true ) );

		$past_consent_expiry = time() > ( $consent_timestamp + ( STICKY_CONSENT_DURATION ) );

		return empty( $consent_timestamp ) || $past_consent_expiry;
	}
	/**
	* Updates the timestamp for the given user and client.
	 *
	 * @param int $user_id The ID of the user to update the timestamp for.
	 * @param int $client_id The ID of the client to update the timestamp for.
	*/

	public function update_timestamp( $user_id, $client_id ) {
		update_user_meta( $user_id, $this->get_meta_key( $client_id ), time() );
	}
	/**
	* Uninstall the plugin.
	 *
	 * @global wpdb $wpdb The WordPress Database Access Abstraction Object.
	*/

	public static function uninstall() {
		global $wpdb;

		// Following query is only possible via a direct query since meta_key is not a fixed string
		// and since it only runs at uninstall, we don't need it cached.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE %s",
				self::META_KEY_PREFIX . '%',
			)
		);
		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $row ) {
			delete_user_meta( $row->user_id, $row->meta_key );
		}
	}
}
