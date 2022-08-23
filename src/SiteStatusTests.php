<?php
namespace OpenIDConnectServer;
use OAuth2;

class SiteStatusTests {
	public static function register_site_status_tests( $tests ) {
		$tests['direct']['oidc-public-key'] = array(
			'label' => __( 'The public key is defined and in the right format', 'wp-openid-connect-server' ),
			'test'  => array( __NAMESPACE__ . '\\' . __CLASS__, 'site_status_test_public_key' ),
		);

		$tests['direct']['oidc-private-key'] = array(
			'label' => __( 'The private key is defined and in the right format', 'wp-openid-connect-server' ),
			'test'  => array( __NAMESPACE__ . '\\' . __CLASS__, 'site_status_test_private_key' ),
		);

		$tests['direct']['oidc-clients'] = array(
			'label' => __( 'One or more clients have been defined correctly', 'wp-openid-connect-server' ),
			'test'  => array( __NAMESPACE__ . '\\' . __CLASS__, 'site_status_test_clients' ),
		);

		return $tests;
	}

	public static function site_status_test_public_key() {
		if ( ! defined( 'OIDC_PUBLIC_KEY' ) ) {
			$label = __( 'The public key constant OIDC_PUBLIC_KEY is not defined.', 'wp-openid-connect-server' );
			$status = 'critical';
			$badge = 'red';
		} elseif (
			0 === strpos( OIDC_PUBLIC_KEY, '-----BEGIN PUBLIC KEY-----' )
			&& '-----END PUBLIC KEY-----' === substr( OIDC_PUBLIC_KEY, - strlen( '-----END PUBLIC KEY-----' ) )
			&& strlen( OIDC_PUBLIC_KEY ) > 50
		) {
			$label = __( 'The public key is defined and in the right format', 'wp-openid-connect-server' );
			$status = 'good';
			$badge = 'green';
		} else {
			$label = __( 'The public key constant OIDC_PUBLIC_KEY is malformed.', 'wp-openid-connect-server' );
			$status = 'critical';
			$badge = 'red';
		}

		return array(
			'label'       => wp_kses_post( $label ),
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'OpenID Connect Server', 'wp-openid-connect-server' ),
				'color' => $badge,
			),
			'description' =>
			'<p>' .
			__( 'You need to provide RSA keys for the OpenID Connect Server to function.', 'wp-openid-connect-server' ) .
			' ' .
			wp_kses_post(
				sprintf(
					// Translators: %s is a URL.
					__( "Please see the <a href=%s>plugin's readme file</a> for details.", 'wp-openid-connect-server' ),
					'"https://github.com/Automattic/wp-openid-connect-server/blob/trunk/README.md"'
				)
			) .
			'</p>',
			'test'        => 'oidc-public-key',
		);
	}

	public static function site_status_test_private_key() {
		if ( ! defined( 'OIDC_PRIVATE_KEY' ) ) {
			$label = __( 'The private key constant OIDC_PRIVATE_KEY is not defined.', 'wp-openid-connect-server' );
			$status = 'critical';
			$badge = 'red';
		} elseif (
			0 === strpos( OIDC_PRIVATE_KEY, '-----BEGIN RSA PRIVATE KEY-----' )
			&& '-----END RSA PRIVATE KEY-----' === substr( OIDC_PRIVATE_KEY, - strlen( '-----END RSA PRIVATE KEY-----' ) )
			&& strlen( OIDC_PRIVATE_KEY ) > 70
		) {
			$label = __( 'The private key is defined and in the right format', 'wp-openid-connect-server' );
			$status = 'good';
			$badge = 'green';
		} else {
			$label = __( 'The private key constant OIDC_PRIVATE_KEY is malformed.', 'wp-openid-connect-server' );
			$status = 'critical';
			$badge = 'red';
		}

		return array(
			'label'       => wp_kses_post( $label ),
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'OpenID Connect Server', 'wp-openid-connect-server' ),
				'color' => $badge,
			),
			'description' =>
			'<p>' .
			__( 'You need to provide RSA keys for the OpenID Connect Server to function.', 'wp-openid-connect-server' ) .
			' ' .
			wp_kses_post(
				sprintf(
					// translators: %s is a URL.
					__( "Please see the <a href=%s>plugin's readme file</a> for details.", 'wp-openid-connect-server' ),
					'"https://github.com/Automattic/wp-openid-connect-server/blob/trunk/README.md"'
				)
			) .
			'</p>',
			'test'        => 'oidc-private-key',
		);
	}

	public static function site_status_test_clients() {
		$clients = function_exists( '\oidc_clients' ) ? \oidc_clients() : array();
		if ( empty( $clients ) ) {
			$label = __( 'No clients have been defined.', 'wp-openid-connect-server' );
			$status = 'critical';
			$badge = 'red';
		} else {
			$all_clients_ok = true;
			foreach ( $clients as $client_id => $client ) {
				$error = false;
				if ( strlen( $client_id ) < 10 ) {
					$error = __( 'The client id (array key) needs to be a random string.', 'wp-openid-connect-server' );
				}
				if ( empty( $client['redirect_uri'] ) ) {
					$error = __( 'You need to specify a redirect_uri.', 'wp-openid-connect-server' );
				}
				if ( ! preg_match( '#^https://#', $client['redirect_uri'] ) ) {
					$error = __( 'The redirect_uri needs to be a HTTPS URL.', 'wp-openid-connect-server' );
				}
				if ( empty( $client['name'] ) ) {
					$error = __( 'You need to specify a name.', 'wp-openid-connect-server' );
				}
				if ( $error ) {
					$label = sprintf(
                        // translators: %s is a random string representing the client id.
                            __('The client %s seems to be malformed.', 'wp-openid-connect-server'),
                            $client_id
                        ) . ' ' . $error;
					$status = 'critical';
					$badge = 'red';
					$all_clients_ok = false;
					break;
				}
			}

			if ( $all_clients_ok ) {
				$label = sprintf(
				// translators: %d is the number of clients that were defined.
					_n( 'The defined client seems to be in the right format', 'The %d defined clients seem to be in the right format', 'wp-openid-connect-server' ),
					count( $clients )
				);
				$status = 'good';
				$badge = 'green';
			}
		}

		return array(
			'label'       => wp_kses_post( $label ),
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'OpenID Connect Server', 'wp-openid-connect-server' ),
				'color' => $badge,
			),
			'description' =>
			'<p>' .
			__( 'You need to define clients for the OpenID Connect Server to function.', 'wp-openid-connect-server' ) .
			' ' .
			wp_kses_post(
				sprintf(
					// Translators: %s is a URL.
					__( "Please see the <a href=%s>plugin's readme file</a> for details.", 'wp-openid-connect-server' ),
					'"https://github.com/Automattic/wp-openid-connect-server/blob/trunk/README.md"'
				)
			) .
			'</p>',
			'test'        => 'oidc-clients',
		);
	}
}
