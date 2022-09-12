<?php

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace OpenIDConnectServer\Overrides\OpenID\Controller;

use OAuth2\OpenID\Controller\AuthorizeController as BaseOpenIDAuthorizeController;

class AuthorizeController extends BaseOpenIDAuthorizeController {
	protected function buildAuthorizeParameters( $request, $response, $user_id ) {
		$params = parent::buildAuthorizeParameters( $request, $response, $user_id );
		if ( ! $params ) {
			return;
		}

		// Generate an id token if needed.
		if ( $this->needsIdToken( $this->getScope() ) && $this->getResponseType() === self::RESPONSE_TYPE_AUTHORIZATION_CODE ) {
			// START MODIFICATION.
			// we obtain response and parse out id_token from it, since we need user claims for the right token, which we don't have access to here
			list( $redirect_uri, $response ) = $this->responseTypes['id_token']->getAuthorizeResponse(
				array( 'client_id' => $this->getClientId(), 'nonce' => $this->getNonce(), 'scope' => $this->getScope(), 'state' => $this->getState(), 'redirect_uri' => $this->getRedirectUri() )
			);
			$params['id_token'] = $response['fragment']['id_token'];
			// END MODIFICATION.
		}

		// Add the nonce to return with the redirect URI.
		$params['nonce'] = $this->getNonce();

		return $params;
	}
}
