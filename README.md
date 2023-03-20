# OpenID Connect Server

- Contributors: wordpressdotorg, akirk, ashfame, psrpinto
- Tags: oidc, oauth, openid, openid connect, oauth server
- Requires at least: 6.0
- Tested up to: 6.1
- Requires PHP: 7.4
- License: [GPLv2](http://www.gnu.org/licenses/gpl-2.0.html)
- Stable tag: 1.1.0
- GitHub Plugin URI: https://github.com/Automattic/wp-openid-connect-server

Use OpenID Connect to log in to other webservices using your own WordPress.

## Description

With this plugin you can use your own WordPress install to authenticate with a webservice that provides [OpenID Connect](https://openid.net/connect/) to implement Single-Sign On (SSO) for your users.

The plugin is currently only configured using constants and hooks as follows:

### Define the RSA keys

If you don't have keys that you want to use yet, generate them using these commands:
~~~console
openssl genrsa -out oidc.key 4096
openssl rsa -in oidc.key -pubout -out public.key
~~~

And make them available to the plugin as follows (this needs to be added before WordPress loads):

~~~php
define( 'OIDC_PUBLIC_KEY', <<<OIDC_PUBLIC_KEY
-----BEGIN RSA PUBLIC KEY-----
...
-----END RSA PUBLIC KEY-----
OIDC_PUBLIC_KEY
);

define( 'OIDC_PRIVATE_KEY', <<<OIDC_PRIVATE_KEY
-----BEGIN RSA PRIVATE KEY-----
...
-----END RSA PRIVATE KEY-----
OIDC_PRIVATE_KEY
);
~~~
Alternatively, you can also put them outside the webroot and load them from the files like this:
~~~php
define( 'OIDC_PUBLIC_KEY', file_get_contents( '/web-inaccessible/oidc.key' ) );
define( 'OIDC_PRIVATE_KEY', file_get_contents( '/web-inaccessible/private.key' ) );
~~~

### Define the clients

Define your clients by adding a filter to `oidc_registered_clients` in a separate plugin file or `functions.php` of your theme or in a MU-plugin like:
~~~php
add_filter( 'oidc_registered_clients', 'my_oidc_clients' );
function my_oidc_clients() {
	return array(
		'client_id_random_string' => array(
			'name' => 'The name of the Client',
			'secret' => 'a secret string',
			'redirect_uri' => 'https://example.com/redirect.uri',
			'grant_types' => array( 'authorization_code' ),
			'scope' => 'openid profile',
		),
	);
}
~~~

### Github Repo
You can report any issues you encounter directly on [Github repo: Automattic/wp-openid-connect-server](https://github.com/Automattic/wp-openid-connect-server)
