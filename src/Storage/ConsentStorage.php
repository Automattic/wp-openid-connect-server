<?php

namespace OpenIDConnectServer\Storage;

const STICKY_CONSENT_DURATION = 7 * DAY_IN_SECONDS;
const META_KEY_PREFIX         = 'oidc_consent_timestamp';

class ConsentStorage {
	private function get_meta_key( $client_id ) : string {
		return META_KEY_PREFIX . '_' . $client_id;
	}

	public function needs_consent( $user_id, $client_id ): bool {
		$consent_timestamp = absint( get_user_meta( $user_id, $this->get_meta_key( $client_id ), true ) );

		$past_consent_expiry = time() > ( $consent_timestamp + ( STICKY_CONSENT_DURATION ) );

		return empty( $consent_timestamp ) || $past_consent_expiry;
	}

	public function update_timestamp( $user_id, $client_id ) {
		update_user_meta( $user_id, $this->get_meta_key( $client_id ), time() );
	}

	public static function uninstall() {
		global $wpdb;

		// Following query is only possible via a direct query since meta_key is not a fixed string
		// and since it only runs at uninstall, we don't need it cached
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE %s",
				'oidc_consent_timestamp_%',
			)
		);
		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $row ) {
			$client_id = substr( $row->meta_key, strlen( 'oidc_consent_timestamp_' ) );
			delete_user_meta( $row->user_id, 'oidc_consent_timestamp_' . $client_id );
		}
	}
}
