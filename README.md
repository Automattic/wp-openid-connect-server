# OpenID Connect Server

- Contributors: wordpressdotorg, akirk, ashfame, psrpinto
- Tags: oidc, oauth, openid, openid connect, oauth server
- Requires at least: 6.0
- Tested up to: 6.5
- Requires PHP: 7.4
- License: [GPLv2](http://www.gnu.org/licenses/gpl-2.0.html)
- Stable tag: 1.3.4
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
-----BEGIN PUBLIC KEY-----
...
-----END PUBLIC KEY-----
OIDC_PUBLIC_KEY
);

define( 'OIDC_PRIVATE_KEY', <<<OIDC_PRIVATE_KEY
-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----
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

### Exclude URL from caching

- `example.com/wp-json/openid-connect/userinfo`: We implement caching exclusion measures for this endpoint by setting `Cache-Control: 'no-cache'` headers and defining the `DONOTCACHEPAGE` constant. If you have a unique caching configuration, please ensure that you manually exclude this URL from caching.

### Github Repo
You can report any issues you encounter directly on [Github repo: Automattic/wp-openid-connect-server](https://github.com/Automattic/wp-openid-connect-server)

## Changelog

### 1.3.4
- Add the autoloader to the uninstall script [#111](https://github.com/Automattic/wp-openid-connect-server/pull/111) props @MariaMozgunova

### 1.3.3

- Fix failing login when Authorize form is non-English [[#108](https://github.com/Automattic/wp-openid-connect-server/pull/108)]
- Improvements in site health tests for key detection [[#104](https://github.com/Automattic/wp-openid-connect-server/pull/104)][[#105](https://github.com/Automattic/wp-openid-connect-server/pull/105)]

### 1.3.2

- Prevent userinfo endpoint from being cached [[#99](https://github.com/Automattic/wp-openid-connect-server/pull/99)]

### 1.3.0

- Return `display_name` as the `name` property [[#87](https://github.com/Automattic/wp-openid-connect-server/pull/87)]
- Change text domain to `openid-connect-server`, instead of `wp-openid-connect-server` [[#88](https://github.com/Automattic/wp-openid-connect-server/pull/88)]

### 1.2.1

- No user facing changes

### 1.2.0

- Add `oidc_user_claims` filter [[#82](https://github.com/Automattic/wp-openid-connect-server/pull/82)]
