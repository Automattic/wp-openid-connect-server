<?php

use OpenIDConnectServer\OpenIDConnectServer;

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

OpenIDConnectServer::uninstall();
