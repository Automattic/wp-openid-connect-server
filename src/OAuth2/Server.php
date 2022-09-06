<?php

namespace OpenIDConnectServer\OAuth2;

use OAuth2\Server as BaseServer;

class Server extends BaseServer {
	protected function createDefaultAuthorizeController() {
		$controller = parent::createDefaultAuthorizeController();
		return $controller;
	}
}
