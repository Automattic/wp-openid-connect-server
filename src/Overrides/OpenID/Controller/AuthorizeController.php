<?php

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace OpenIDConnectServer\Overrides\OpenID\Controller;

use OAuth2\OpenID\Controller\AuthorizeController as BaseOpenIDAuthorizeController;
use OAuth2\OpenID\ResponseType\IdToken;

class AuthorizeController extends BaseOpenIDAuthorizeController {
	protected function buildAuthorizeParameters( $request, $response, $user_id ) {
		$params = parent::buildAuthorizeParameters( $request, $response, $user_id );
		if ( ! $params ) {
			return;
		}

		// Generate an id token if needed.
		if ( $this->needsIdToken( $this->getScope() ) && $this->getResponseType() === self::RESPONSE_TYPE_AUTHORIZATION_CODE ) {
			$userClaims         = $this->clientStorage->getUserClaims( $user_id, $params['scope'] );

			/** @var IdToken $id_token */
			$id_token = $this->responseTypes['id_token'];
			$params['id_token'] = $id_token->createIdToken( $this->getClientId(), $user_id, $this->getNonce(), $userClaims );
		}

		return $params;
	}
}
