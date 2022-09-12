<?php

namespace OpenIDConnectServer\Templating;

class Templating {
	private Loader $loader;

	public function __construct() {
		$this->loader = new Loader();
	}

	public function render( string $template_name, array $data = array() ): string {
		$data['templates'] = $this;

		ob_start();
		$this->loader
			->set_template_data( $data )
			->get_template_part( $template_name );

		return ob_get_clean();
	}

	public function partial( string $template_name ) {
		$this->loader->get_template_part( $template_name );
	}
}
