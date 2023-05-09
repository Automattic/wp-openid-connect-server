<?php

namespace OpenIDConnectServer;

use OAuth2\Request;
use OAuth2\Response;
use OpenIDConnectServer\Http\Handlers\AuthenticateHandler;
use OpenIDConnectServer\Http\Handlers\AuthorizeHandler;
use OpenIDConnectServer\Http\Handlers\ConfigurationHandler;
use OpenIDConnectServer\Http\Handlers\TokenHandler;
use OpenIDConnectServer\Http\Handlers\UserInfoHandler;
use OpenIDConnectServer\Http\Handlers\WebKeySetsHandler;
use OpenIDConnectServer\Http\Router;
use OpenIDConnectServer\Storage\AuthorizationCodeStorage;
use OpenIDConnectServer\Storage\ClientCredentialsStorage;
use OpenIDConnectServer\Storage\ConsentStorage;
use OpenIDConnectServer\Storage\PublicKeyStorage;
use OpenIDConnectServer\Storage\UserClaimsStorage;
use OAuth2\Server;

class OpenIDConnectServer {
	private string $public_key;
	private array $clients;
	private Router $router;
	private ConsentStorage $consent_storage;

	public function __construct( string $public_key, string $private_key, array $clients ) {
		$this->public_key      = $public_key;
		$this->clients         = $clients;
		$this->router          = new Router();
		$this->consent_storage = new ConsentStorage();

		$config = array(
			'use_jwt_access_tokens' => true,
			'use_openid_connect'    => true,
			'issuer'                => home_url( '/' ),
		);

		$server = new Server( new AuthorizationCodeStorage(), $config );
		$server->addStorage( new PublicKeyStorage( $public_key, $private_key ), 'public_key' );
		$server->addStorage( new ClientCredentialsStorage( $clients ), 'client_credentials' );
		$server->addStorage( new UserClaimsStorage(), 'user_claims' );

		// Declare rest routes.
		$this->router->add_rest_route(
			'token',
			new TokenHandler( $server ),
			array( 'POST' ),
			$this->expected_arguments_specification( 'token' ),
		);
		$this->router->add_rest_route(
			'authorize',
			new AuthorizeHandler( $server, $this->consent_storage ),
			array( 'GET', 'POST' ),
			$this->expected_arguments_specification( 'authorize' ),
		);
		$this->router->add_rest_route(
			'userinfo',
			new UserInfoHandler( $server ),
			array( 'GET', 'POST' ),
			$this->expected_arguments_specification( 'userinfo' ),
		);

		// Declare non-rest routes.
		$this->router->add_route( '.well-known/jwks.json', new WebKeySetsHandler( $this->public_key ) );
		$this->router->add_route( '.well-known/openid-configuration', new ConfigurationHandler() );
		add_action( 'login_form_openid-authenticate', array( $this, 'authenticate_handler' ) );

		// Cleanup.
		$this->setup_cron_hook();
	}

	public function authenticate_handler() {
		$request  = Request::createFromGlobals();
		$response = new Response();

		$authenticate_handler = new AuthenticateHandler( $this->consent_storage, $this->clients );
		$authenticate_handler->handle( $request, $response );
		exit;
	}

	private function expected_arguments_specification( $route ) {
		switch ( $route ) {
			case 'authorize':
				return array(
					'client_id'     => array(
						'type'     => 'string',
						'required' => true,
					),
					'redirect_uri'  => array(
						'type' => 'string',
					),
					'response_type' => array(
						'type'     => 'string',
						'required' => true,
					),
					'state'         => array(
						'type' => 'string',
					),
				);
			case 'token':
				return array(
					'grant_type'    => array(
						'type'     => 'string',
						'required' => true,
					),
					'client_id'     => array(
						'type'     => 'string',
						'required' => true,
					),
					'client_secret' => array(
						'type'     => 'string',
						'required' => true,
					),
					'redirect_uri'  => array(
						'type'     => 'string',
						'required' => true,
					),
					'code'          => array(
						'type'     => 'string',
						'required' => true,
					),
				);
			case 'userinfo':
				return array();
		}
	}

	public function setup_cron_hook() {
		if ( ! wp_next_scheduled( 'oidc_cron_hook' ) ) {
			wp_schedule_event( time(), 'weekly', 'oidc_cron_hook' );
		}
	}

	/**
	 * This function is invoked from uninstall.php
	 *
	 * As of v1.0 we have two things that are being stored and should be removed on uninstall:
	 * 1) Consent storage
	 * 2) Auth code storage
	 */
	public static function uninstall() {
		ConsentStorage::uninstall();
		AuthorizationCodeStorage::uninstall();
	}
}
