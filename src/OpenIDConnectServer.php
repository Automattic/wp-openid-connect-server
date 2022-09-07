<?php

namespace OpenIDConnectServer;

use OpenIDConnectServer\Http\Handlers\AuthenticateHandler;
use OpenIDConnectServer\Http\Handlers\AuthorizeHandler;
use OpenIDConnectServer\Http\Handlers\ConfigurationHandler;
use OpenIDConnectServer\Http\Handlers\WebKeySetsHandler;
use OpenIDConnectServer\Http\Handlers\TokenHandler;
use OpenIDConnectServer\Http\Handlers\UserInfoHandler;
use OpenIDConnectServer\Http\Router;
use OpenIDConnectServer\Overrides\Server;
use OpenIDConnectServer\Storage\AuthorizationCodeStorage;
use OpenIDConnectServer\Storage\ClientCredentialsStorage;
use OpenIDConnectServer\Storage\ConsentStorage;
use OpenIDConnectServer\Storage\PublicKeyStorage;
use OpenIDConnectServer\Storage\UserClaimsStorage;

class OpenIDConnectServer {
	private string $public_key;
	private array $clients;
	private Router $router;
	private ConsentStorage $consent_storage;
	private Templating $templating;

	public function __construct( string $public_key, string $private_key, array $clients ) {
		$this->public_key      = $public_key;
		$this->clients         = $clients;
		$this->router          = new Router();
		$this->consent_storage = new ConsentStorage();
		$this->templating      = new Templating( __DIR__ . '/Template');

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
		$this->router->add_rest_route( 'token', new TokenHandler( $server ), array( 'POST' ) );
		$this->router->add_rest_route(
			'authorize',
			new AuthorizeHandler( $server, $this->consent_storage ),
			array( 'GET', 'POST' )
		);
		$this->router->add_rest_route( 'userinfo', new UserInfoHandler( $server ), array( 'GET', 'POST' ) );

		// Declare non-rest routes.
		$this->router->add_route( '.well-known/jwks.json', new WebKeySetsHandler( $this->public_key ) );
		$this->router->add_route( '.well-known/openid-configuration', new ConfigurationHandler() );
		$this->router->add_route( 'openid-connect/authenticate', new AuthenticateHandler( $this->consent_storage, $this->templating, $this->clients ) );
	}
}
