<?php

// phpcs:ignoreFile

namespace OpenIDConnectServer\OAuth2;

use OAuth2\OpenID\Controller\AuthorizeController as BaseAuthorizeController;
use OAuth2\OpenID\Controller\AuthorizeControllerInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

class AuthorizeController extends BaseAuthorizeController implements AuthorizeControllerInterface {
	/**
	 * @var mixed
	 */
	private $nonce;

	/**
	 * @TODO: add dependency injection for the parameters in this method
	 *
	 * @param RequestInterface  $request
	 * @param ResponseInterface $response
	 * @param mixed             $user_id
	 *
	 * @return array
	 */
	protected function buildAuthorizeParameters( $request, $response, $user_id ) {
		if ( ! $params = parent::buildAuthorizeParameters( $request, $response, $user_id ) ) {
			return;
		}

		// Generate an id token if needed.
		if ( $this->needsIdToken( $this->getScope() ) && $this->getResponseType() == self::RESPONSE_TYPE_AUTHORIZATION_CODE ) {
			// START MODIFICATION.
			$userClaims         = $this->clientStorage->getUserClaims( $user_id, $params['scope'] );
			$params['id_token'] = $this->responseTypes['id_token']->createIdToken( $this->getClientId(), $user_id, $this->nonce, $userClaims );
			// END MODIFICATION.
		}

		// add the nonce to return with the redirect URI
		$params['nonce'] = $this->nonce;

		return $params;
	}
}
