<?php

namespace OpenIDConnectServer\Templating;

class Templating {
	private string $path_to_templates_dir;

	public function __construct( string $path_to_templates_dir ) {
		$this->path_to_templates_dir = $path_to_templates_dir;
	}

	public function render( string $template_name, array $parameters ): string {
		ob_start();
		require "$this->path_to_templates_dir/$template_name";

		return ob_get_clean();
	}
}
