<?php
namespace OpenIDConnectServer;
use OAuth2;

class OpenIDConnectServer {
	public function __construct() {
		add_filter( 'site_status_tests', array( SiteStatusTests, 'register_site_status_tests' ) );

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
			<?php if ( ! current_user_can( apply_filters( 'oidc_minimal_capability', 'edit_posts' ) ) ) : ?>
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
