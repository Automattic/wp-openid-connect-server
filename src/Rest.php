<?php

namespace OpenIDConnectServer;

use OAuth2\Request;

const STICKY_CONSENT_DURATION = 7 * DAY_IN_SECONDS;

class Rest {
	private $server;

	const NAMESPACE = 'openid-connect';

	public function __construct( $server ) {
		$this->server = $server;
		add_action( 'rest_api_init', array( $this, 'add_rest_routes' ) );
	}

	public function add_rest_routes() {
		register_rest_route(
			self::NAMESPACE,
			'userinfo',
			array(
				'methods'             => 'GET,POST',  // MUST support both GET and POST.
				'callback'            => array( $this, 'userinfo' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function userinfo() {
		$this->server->handleUserInfoRequest( Request::createFromGlobals() )->send();
		exit;
	}

	public function is_consent_needed(): bool {
		$current_user_id   = get_current_user_id();
		$consent_timestamp = absint( get_user_meta( $current_user_id, 'oidc_consent_timestamp', true ) );

		$past_consent_expiry = time() > ( $consent_timestamp + ( STICKY_CONSENT_DURATION ) );

		return empty( $consent_timestamp ) || $past_consent_expiry;
	}

	public function update_consent_timestamp() {
		update_user_meta( get_current_user_id(), 'oidc_consent_timestamp', time() );
	}
}
