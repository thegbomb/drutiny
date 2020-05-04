<?php

namespace Drutiny\Http;

use Psr\Http\Message\RequestInterface;

interface MiddlewareInterface {
  public function handle(RequestInterface $request);
}
