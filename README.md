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

## Overriding templates
The pages provided by this plugin are rendered using templates. If you so wish, you can use your own templates instead of this plugins's [default templates](templates).

To do so, you should create an `openid-connect/` directory under your theme, containing only the templates you wish to override. For example, if you wanted to override `authenticate/main.php` and `authenticate/forbidden.php` you would create them under an `openid-connect/` directory in your theme:

```shell
wp-content/themes/my-theme/
 │── openid-connect/
 │──── main.php
 └──── forbidden.php
```

If your theme is a child theme, this plugin will first look for templates under the child theme, and then in the parent theme. If it doesn't find a template, it will fall back to using the default template.

### Data
Templates are passed a single `$data` variable containing the values necessary to render said template. For example, you can access the name of the OIDC client as follows:

```php
// wp-content/themes/my-theme/main.php

/** @var stdClass $data **/

/** @var string $client_name The OIDC client name */
$client_name = $data->client_name;
```

You can of course also call any other WordPress function, like you would in any other file in your theme.

### Partials
In your templates, you can include partial templates (aka partials) by calling `$data->templates->partial()`:

```php
// wp-content/themes/my-theme/main.php

/** @var stdClass $data **/

/** @var \OpenIDConnectServer\Templating\Templating $templates */
$templates = $data->templates;

// Renders the <form> in the 'authenticate/form.php' partial.
$templates->partial( 'authenticate/form' )
```

Partials are also passed the `$data` variable.
