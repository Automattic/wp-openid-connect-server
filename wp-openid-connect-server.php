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
		new OpenIDConnectServer();
	}
);
