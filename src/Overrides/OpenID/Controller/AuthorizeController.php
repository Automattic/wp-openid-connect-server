<?php

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace OpenIDConnectServer\Overrides\OpenID\Controller;

use OAuth2\OpenID\Controller\AuthorizeController as BaseOpenIDAuthorizeController;
use OAuth2\OpenID\ResponseType\IdToken;
use OAuth2\Storage\ClientInterface;
use OAuth2\ScopeInterface;
use OpenIDConnectServer\Storage\UserClaimsStorage;

class AuthorizeController extends BaseOpenIDAuthorizeController {
	private UserClaimsStorage $user_claims_storage;

	public function __construct( ClientInterface $clientStorage, array $responseTypes, array $config, ScopeInterface $scopeUtil, UserClaimsStorage $userClaimsStorage ) {
		parent::__construct( $clientStorage, $responseTypes, $config, $scopeUtil );
		$this->user_claims_storage = $userClaimsStorage;
	}

	protected function buildAuthorizeParameters( $request, $response, $user_id ) {
		$params = parent::buildAuthorizeParameters( $request, $response, $user_id );
		if ( ! $params ) {
			return;
		}

		if ( ! $this->needsIdToken( $this->getScope() ) || self::RESPONSE_TYPE_AUTHORIZATION_CODE !== $this->getResponseType() ) {
			return $params;
		}

		/** @var IdToken $id_token */
		$id_token = $this->responseTypes['id_token'];

		$userClaims         = $this->user_claims_storage->getUserClaims( $user_id, $params['scope'] );
		$params['id_token'] = $id_token->createIdToken( $this->getClientId(), $user_id, $this->getNonce(), $userClaims );

		return $params;
	}
}
