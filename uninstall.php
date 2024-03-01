<?php

use OpenIDConnectServer\OpenIDConnectServer;

require_once __DIR__ . '/vendor/autoload.php';

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

OpenIDConnectServer::uninstall();
