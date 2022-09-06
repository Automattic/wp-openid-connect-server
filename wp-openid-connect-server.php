<?php
/**
 * Plugin Name: OpenID Connect Server
 * Description: Use OpenID Connect to log in to other webservices using your own WordPress.
 * Author: Automattic
 * Author URI: https://automattic.com/
 * Plugin URI: https://github.com/Automattic/wp-openid-connect-server
 * Version: 1.0
 */

namespace OpenIDConnectServer;

require_once __DIR__ . '/vendor/autoload.php';

add_action(
	'wp_loaded',
	function () {
		new SiteStatusTests();

		if ( ! defined( 'OIDC_PUBLIC_KEY' ) || ! defined( 'OIDC_PRIVATE_KEY' ) ) {
			// Please follow instructions in readme.txt for defining the keys.
			return;
		}

		$clients = function_exists( '\oidc_clients' ) ? \oidc_clients() : array();
		new OpenIDConnectServer( OIDC_PUBLIC_KEY, OIDC_PRIVATE_KEY, $clients );
	}
);
