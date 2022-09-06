<?php

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace OpenIDConnectServer\OAuth2;

use OAuth2\Server as BaseServer;
use OAuth2\OpenID\Controller\AuthorizeController as BaseAuthorizeController;

class Server extends BaseServer {
	protected function createDefaultAuthorizeController() {
		$controller = parent::createDefaultAuthorizeController();

		if ( $controller instanceof BaseAuthorizeController ) {
			// Override it with our own controller.
			$config     = array_intersect_key( $this->config, array_flip( explode( ' ', 'allow_implicit enforce_state require_exact_redirect_uri' ) ) );
			$controller = new AuthorizeController( $this->storages['client'], $this->responseTypes, $config, $this->getScopeUtil() );
		}

		return $controller;
	}
}
