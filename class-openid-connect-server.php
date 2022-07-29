<?php
namespace OpenIDConnectServer;
use OAuth2;

class OpenIDConnectServer {
	public function __construct() {
		add_filter( 'site_status_tests', array( $this, 'site_status_tests' ) );

		// Please follow the instructions in the readme for defining these.
		$public_key = defined( 'OIDC_PUBLIC_KEY' ) ? OIDC_PUBLIC_KEY : false;
		if ( ! $public_key ) {
			return false;
		}
		$private_key = defined( 'OIDC_PRIVATE_KEY' ) ? OIDC_PRIVATE_KEY : false;
		if ( ! $private_key ) {
			return false;
		}

		$config = array(
			'use_jwt_access_tokens' => true,
			'use_openid_connect' => true,
			'issuer' => home_url( '/' ),
		);

		$server = new OAuth2\Server( new OAuth2_Storage(), $config );

		$server->addStorage(
			new OAuth2\Storage\Memory(
				array(
					'keys' => compact( 'private_key', 'public_key' ),
				)
			),
			'public_key'
		);

		// Add REST endpoints.
		new Rest( $server );

		add_action( 'template_redirect', array( $this, 'jwks' ) );
		add_action( 'template_redirect', array( $this, 'openid_configuration' ) );
		add_action( 'template_redirect', array( $this, 'openid_authenticate' ) );
	}

	public function site_status_tests( $tests ) {
		$tests['direct']['oidc-public-key'] = array(
			'label' => __( 'The public key is defined and in the right format', 'wp-openid-connect-server' ),
			'test'  => array( $this, 'site_status_test_public_key' ),
		);

		$tests['direct']['oidc-private-key'] = array(
			'label' => __( 'The private key is defined and in the right format', 'wp-openid-connect-server' ),
			'test'  => array( $this, 'site_status_test_private_key' ),
		);

		$tests['direct']['oidc-clients'] = array(
			'label' => __( 'One or more clients have been defined correctly', 'wp-openid-connect-server' ),
			'test'  => array( $this, 'site_status_test_clients' ),
		);

		return $tests;
	}

	public function site_status_test_public_key() {
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

	public function site_status_test_private_key() {
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

	public function site_status_test_clients() {
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
						__( 'The client %s seems to be malformed.', 'wp-openid-connect-server' ),
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

	public function jwks() {
		if ( $_SERVER['REQUEST_URI'] !== '/.well-known/jwks.json' ) {
			return;
		}
		status_header( 200 );
		header( 'Content-type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );

		$options = array(
			'use' => 'sig',
			'alg' => 'RS256',
		);

		$keyFactory = new \Strobotti\JWK\KeyFactory();
		echo '{"keys":[';
		echo $keyFactory->createFromPem( OIDC_PUBLIC_KEY, $options );
		echo ']}';
		exit;
	}

	public function openid_configuration() {
		if ( $_SERVER['REQUEST_URI'] !== '/.well-known/openid-configuration' ) {
			return;
		}
		status_header( 200 );
		header( 'Content-type: application/json' );
		header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( array(
			'issuer' => home_url( '/' ),
			'authorization_endpoint' => rest_url( 'openid-connect/authorize' ),
			'token_endpoint' => rest_url( 'openid-connect/token' ),
			'userinfo_endpoint' => rest_url( 'openid-connect/userinfo' ),
			'jwks_uri' => home_url( '/.well-known/jwks.json' ),
			'scopes_supported' => array( 'openid', 'profile' ),
			'response_types_supported' => array( 'code' ),
			'id_token_signing_alg_values_supported' => array( 'RS256' ),
		) );
		exit;
	}

	public function openid_authenticate() {
		if ( 0 !== strpos( $_SERVER['REQUEST_URI'], '/openid-connect/authenticate' ) ) {
			return;
		}
		$request = OAuth2\Request::createFromGlobals();
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		status_header( 200 );
		?>
		<html>
		<html <?php language_attributes(); ?> class="no-js no-svg">
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php wp_head(); ?>
			<style>
				.openid-connect-authentication {
					padding: 4em;
				}
			</style>
		</head>

		<body <?php body_class( 'openid-connect-authentication' ); ?>>
			<h1><?php esc_html_e( 'OpenID Connect', 'wp-openid-connect-server' ); ?></h1>
			<p><?php echo esc_html(
				sprintf(
					// translators: %s is a username.
					__( 'Hi %s!', 'wp-openid-connect-server' ),
					wp_get_current_user()->user_nicename
				)
			); ?></p>
			<?php if ( ! current_user_can( apply_filters( 'oidc_minimal_capability', 'contributor' ) ) ) : ?>
				<p><?php esc_html_e( "Unfortunately your user doesn't have sufficient permissions to use OpenID Connect on this server.", 'wp-openid-connect-server' ); ?></p>
			<?php else : ?>
			<form method="post" action="<?php echo esc_url( rest_url( Rest::NAMESPACE . '/authorize' ) ); ?>">
				<?php wp_nonce_field( 'wp_rest' ); /* The nonce will give the REST call the userdata. */ ?>
				<?php foreach ( $request->getAllQueryParameters() as $key => $value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<?php endforeach; ?>
				<p><label><?php echo wp_kses(
					sprintf(
						__( 'Do you want to log in to <em>%1$s</em> with your <em>%2$s</em> account?', 'wp-openid-connect-server' ),
						OAuth2_Storage::getClientName( $request->query( 'client_id' ) ),
						get_bloginfo( 'name' )
					),
					array(
						'em' => array()
					)
				); ?></label></p>
				<input type="submit" name="authorize" value="<?php esc_attr_e( 'Authorize', 'wp-openid-connect-server' ); ?>" />
				<a href="<?php echo esc_url( home_url() ); ?>" target="_top"><?php esc_html_e( 'Cancel', 'wp-openid-connect-server' ); ?></a>
			</form>
			<?php endif; ?>
			<?php wp_footer(); ?>
		</body></html>
		<?php
		exit;
	}
}
