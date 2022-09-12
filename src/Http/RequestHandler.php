<?php

namespace OpenIDConnectServer\Http;

use OAuth2\Request;
use OAuth2\Response;

abstract class RequestHandler {
	abstract public function handle( Request $request, Response $response ): Response;
}
