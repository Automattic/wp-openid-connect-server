# OpenID Connect Server

Use OpenID Connect to log in to other webservices using your own WordPress.

**Contributors:** akirk, ashfame, psrpinto
**Tags:** oidc, openid, connect, server
**Requires at least:** 5.0
**Tested up to:** 6.1
**Requires PHP:** 7.1
**License:** [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)
**Stable tag:** trunk
**GitHub Plugin URI:** https://github.com/Automattic/wp-openid-connect-server

## Description

With this plugin you can use your own WordPress install to authenticate with a webservice that provides [OpenID Connect](https://openid.net/connect/) to implement Single-Sign On (SSO) for your users.

The plugin is currently only configured using constants and hooks as follows:

### Define the RSA keys

If you don't have keys that you want to use yet, generate them using these commands:
```
openssl genrsa -out oidc.key 4096
openssl rsa -in oidc.key -pubout -out public.key
```

And make them available to the plugin as follows (this needs to be added before WordPress loads):

```
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
```
Alternatively, you can also put them outside of the webroot and load them from the files like this:
```
define( 'OIDC_PUBLIC_KEY', file_get_contents( '/web-inaccessible/oidc.key' ) );
define( 'OIDC_PRIVATE_KEY', file_get_contents( '/web-inaccessible/private.key' ) );
```

### Define the clients

Define your clients like so (this needs to be added before WordPress loads):
```
function oidc_clients() {
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
```
