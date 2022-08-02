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
// Override to incorporate this fix: https://github.com/bshaffer/oauth2-server-php/issues/449#issuecomment-227531175
require_once __DIR__ . '/class-authorization-controller.php';

require_once __DIR__ . '/class-rest.php';
require_once __DIR__ . '/class-site-status-tests.php';
require_once __DIR__ . '/class-oauth2-storage.php';
require_once __DIR__ . '/class-openid-connect-server.php';

add_action( 'wp_loaded', function() {
	new OpenIDConnectServer();
} );