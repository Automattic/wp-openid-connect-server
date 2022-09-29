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
}
