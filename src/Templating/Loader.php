<?php

namespace OpenIDConnectServer\Templating;

use Gamajo_Template_Loader as BaseLoader;

class Loader extends BaseLoader {
	protected $filter_prefix = 'wp-openid-connect-server';

	protected $theme_template_directory = 'openid-connect';

	protected $plugin_directory = __DIR__ . '/../../';

	protected $plugin_template_directory = 'templates';
}
