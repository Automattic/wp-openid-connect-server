<?php

//phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace OpenIDConnectServer\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface;

class UserClaimsStorage implements UserClaimsInterface {
	public function getUserClaims( $user_id, $scope ) {
		// We use WordPress user_login as the user identifier.
		$user_login = $user_id;

		$claims = array(
			// We expose the scope here so that it's in the token (unclear from the specs but the userinfo endpoint reads the scope from the token).
			'scope' => $scope,
		);

		if ( ! empty( $_REQUEST['nonce'] ) ) {
			$claims['nonce'] = sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );
		}

		$scopes = explode( ' ', $scope );
		if ( ! in_array( 'profile', $scopes, true ) ) {
			return $claims;
		}

		$user = get_user_by( 'login', $user_login );
		if ( ! $user ) {
			return $claims;
		}

		$field_map = array(
			'username'    => 'user_login',
			'name'        => 'display_name',
			'given_name'  => 'first_name',
			'family_name' => 'last_name',
			'nickname'    => 'user_nicename',
		);

		// Is email scope requested?
		if(in_array('email', $scopes, true)) {
			$field_map['email'] = 'user_email';
		}

		// Is phone scope requested?
		if(in_array('phone', $scopes, true)) {
			$field_map['phone'] = 'user_phone';
		}

		foreach ( $field_map as $key => $value ) {
			if ( $user->$value ) {
				$claims[ $key ] = $user->$value;
			}
		}

		// Is profile scope requested, add picture (user avatar)?
		if(in_array( 'profile', $scopes, true )) {
			$claims['picture'] = get_avatar_url( $user->user_email );
		}

		return apply_filters( 'oidc_user_claims', $claims, $user );
	}
}
