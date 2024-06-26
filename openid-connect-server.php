<?php
/**
 * Plugin Name:       OpenID Connect Server
 * Plugin URI:        https://github.com/Automattic/wp-openid-connect-server
 * Description:       Use OpenID Connect to log in to other webservices using your own WordPress.
 * Version:           1.3.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WordPress.Org Community
 * Author URI:        https://wordpress.org/
 * License:           GPL v2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       openid-connect-server
 */

use OpenIDConnectServer\OpenIDConnectServer;
use OpenIDConnectServer\SiteStatusTests;

require_once __DIR__ . '/vendor/autoload.php';

add_action(
	'wp_loaded',
	function () {
		new SiteStatusTests();

		if ( ! defined( 'OIDC_PUBLIC_KEY' ) || ! defined( 'OIDC_PRIVATE_KEY' ) ) {
			// Please follow instructions in readme.txt for defining the keys.
			return;
		}

		$clients = apply_filters( 'oidc_registered_clients', array() ); // Currently the only way to add clients is to use this filter.
		new OpenIDConnectServer( OIDC_PUBLIC_KEY, OIDC_PRIVATE_KEY, $clients );
	}
);
