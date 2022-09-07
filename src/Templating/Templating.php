<?php

namespace OpenIDConnectServer\Templating;

class Templating {

	public function __construct() {
		$this->loader = new Loader();
	}

	public function render( string $template_name, array $parameters ): string {
		ob_start();
		$this->loader
			->set_template_data( $parameters, 'parameters' )
			->get_template_part( $template_name );

		return ob_get_clean();
	}
}
