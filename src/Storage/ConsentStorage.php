<?php

namespace OpenIDConnectServer\Storage;

const STICKY_CONSENT_DURATION = 7 * DAY_IN_SECONDS;
const META_KEY                = 'oidc_consent_timestamp';

class ConsentStorage {
	public function needs_consent( $user_id ): bool {
		$consent_timestamp = absint( get_user_meta( $user_id, META_KEY, true ) );

		$past_consent_expiry = time() > ( $consent_timestamp + ( STICKY_CONSENT_DURATION ) );

		return empty( $consent_timestamp ) || $past_consent_expiry;
	}

	public function update_timestamp( $user_id ) {
		update_user_meta( $user_id, META_KEY, time() );
	}
}
