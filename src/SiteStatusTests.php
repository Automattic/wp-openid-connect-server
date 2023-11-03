<?php

namespace OpenIDConnectServer;

class SiteStatusTests {
	public function __construct() {
		add_filter( 'site_status_tests', array( $this, 'register_site_status_tests' ) );
	}

	public function register_site_status_tests( $tests ): array {
		$tests['direct']['oidc-public-key'] = array(
			'label' => __( 'The public key is defined and in the right format', 'openid-connect-server' ),
			'test'  => array( $this, 'site_status_test_public_key' ),
		);

		$tests['direct']['oidc-private-key'] = array(
			'label' => __( 'The private key is defined and in the right format', 'openid-connect-server' ),
			'test'  => array( $this, 'site_status_test_private_key' ),
		);

		$tests['direct']['oidc-clients'] = array(
			'label' => __( 'One or more clients have been defined correctly', 'openid-connect-server' ),
			'test'  => array( $this, 'site_status_test_clients' ),
		);

		return $tests;
	}

	public function site_status_test_public_key(): array {
		$key_is_defined            = defined( 'OIDC_PUBLIC_KEY' );
		$key_has_valid_pem_headers = (bool) preg_match(
			'/^-----BEGIN\s.*PUBLIC KEY-----.*-----END\s.*PUBLIC KEY-----$/s',
			OIDC_PUBLIC_KEY
		);

		if ( ! $key_is_defined ) {
			$label  = __( 'The public key constant OIDC_PUBLIC_KEY is not defined.', 'openid-connect-server' );
			$status = 'critical';
			$badge  = 'red';
		} elseif ( $key_has_valid_pem_headers ) {
			$label  = __( 'The public key is defined and in the right format', 'openid-connect-server' );
			$status = 'good';
			$badge  = 'green';
		} else {
			$label  = __( 'The public key constant OIDC_PUBLIC_KEY is malformed.', 'openid-connect-server' );
			$status = 'critical';
			$badge  = 'red';
		}

		return array(
			'label'       => wp_kses_post( $label ),
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'OpenID Connect Server', 'openid-connect-server' ),
				'color' => $badge,
			),
			'description' =>
				'<p>' .
				__( 'You need to provide RSA keys for the OpenID Connect Server to function.', 'openid-connect-server' ) .
				' ' .
				wp_kses_post(
					sprintf(
					// Translators: %s is a URL.
						__( "Please see the <a href=%s>plugin's readme file</a> for details.", 'openid-connect-server' ),
						'"https://github.com/Automattic/wp-openid-connect-server/blob/main/README.md"'
					)
				) .
				'</p>',
			'test'        => 'oidc-public-key',
		);
	}

	public function site_status_test_private_key(): array {
		$key_is_defined            = defined( 'OIDC_PRIVATE_KEY' );
		$key_has_valid_pem_headers = (bool) preg_match(
			'/^-----BEGIN\s.*PRIVATE KEY-----.*-----END\s.*PRIVATE KEY-----$/s',
			OIDC_PRIVATE_KEY
		);

		if ( ! $key_is_defined ) {
			$label  = __( 'The private key constant OIDC_PRIVATE_KEY is not defined.', 'openid-connect-server' );
			$status = 'critical';
			$badge  = 'red';
		} elseif ( $key_has_valid_pem_headers ) {
			$label  = __( 'The private key is defined and in the right format', 'openid-connect-server' );
			$status = 'good';
			$badge  = 'green';
		} else {
			$label  = __( 'The private key constant OIDC_PRIVATE_KEY is malformed.', 'openid-connect-server' );
			$status = 'critical';
			$badge  = 'red';
		}

		return array(
			'label'       => wp_kses_post( $label ),
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'OpenID Connect Server', 'openid-connect-server' ),
				'color' => $badge,
			),
			'description' =>
				'<p>' .
				__( 'You need to provide RSA keys for the OpenID Connect Server to function.', 'openid-connect-server' ) .
				' ' .
				wp_kses_post(
					sprintf(
					// translators: %s is a URL.
						__( "Please see the <a href=%s>plugin's readme file</a> for details.", 'openid-connect-server' ),
						'"https://github.com/Automattic/wp-openid-connect-server/blob/trunk/README.md"'
					)
				) .
				'</p>',
			'test'        => 'oidc-private-key',
		);
	}

	public function site_status_test_clients(): array {
		$clients = apply_filters( 'oidc_registered_clients', array() );
		if ( empty( $clients ) ) {
			$label  = __( 'No clients have been defined.', 'openid-connect-server' );
			$status = 'critical';
			$badge  = 'red';
		} else {
			$all_clients_ok = true;
			foreach ( $clients as $client_id => $client ) {
				$error = false;
				if ( strlen( $client_id ) < 10 ) {
					$error = __( 'The client id (array key) needs to be a random string.', 'openid-connect-server' );
				}
				if ( empty( $client['redirect_uri'] ) ) {
					$error = __( 'You need to specify a redirect_uri.', 'openid-connect-server' );
				}
				if ( ! preg_match( '#^https://#', $client['redirect_uri'] ) ) {
					$error = __( 'The redirect_uri needs to be a HTTPS URL.', 'openid-connect-server' );
				}
				if ( empty( $client['name'] ) ) {
					$error = __( 'You need to specify a name.', 'openid-connect-server' );
				}
				if ( $error ) {
					$label = sprintf( // translators: %s is a random string representing the client id.
						__( 'The client %1$s seems to be malformed. %2$s', 'openid-connect-server' ),
						$client_id,
						$error
					);

					$status         = 'critical';
					$badge          = 'red';
					$all_clients_ok = false;
					break;
				}
			}

			if ( $all_clients_ok ) {
				$label  = _n( 'The defined client seems to be in the right format', 'The defined clients seem to be in the right format', count( $clients ), 'openid-connect-server' );
				$status = 'good';
				$badge  = 'green';
			}
		}

		return array(
			'label'       => wp_kses_post( $label ),
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'OpenID Connect Server', 'openid-connect-server' ),
				'color' => $badge,
			),
			'description' =>
				'<p>' .
				__( 'You need to define clients for the OpenID Connect Server to function.', 'openid-connect-server' ) .
				' ' .
				wp_kses_post(
					sprintf(
					// Translators: %s is a URL.
						__( "Please see the <a href=%s>plugin's readme file</a> for details.", 'openid-connect-server' ),
						'"https://github.com/Automattic/wp-openid-connect-server/blob/trunk/README.md"'
					)
				) .
				'</p>',
			'test'        => 'oidc-clients',
		);
	}
}
