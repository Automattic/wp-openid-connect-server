<?php
/**
 * Plugin Name:       OpenID Connect Server
 * Plugin URI:        https://github.com/Automattic/wp-openid-connect-server
 * Description:       Use OpenID Connect to log in to other webservices using your own WordPress.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WordPress.Org Community
 * Author URI:        https://wordpress.org/
 * License:           GPL v2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       openid-connect-server
 */

use OpenIDConnectServer\Admin\SettingsPage;
use OpenIDConnectServer\Configuration;
use OpenIDConnectServer\OpenIDConnectServer;
use OpenIDConnectServer\SiteStatusTests;

require_once __DIR__ . '/vendor/autoload.php';

if ( is_admin() ) {
	new SettingsPage();
}

add_action(
	'wp_loaded',
	function () {
		new SiteStatusTests();

		if ( ! Configuration::has_required_keys() ) {
			// Please follow instructions in readme.txt or configure the keys in wp-admin.
			return;
		}

		new OpenIDConnectServer(
			Configuration::get_public_key(),
			Configuration::get_private_key(),
			Configuration::get_clients()
		);
	}
);
