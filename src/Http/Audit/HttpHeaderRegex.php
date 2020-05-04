<?php

namespace Drutiny\Http\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 *
 * @Param(
 *  name = "header",
 *  description = "The HTTP header to check the value of.",
 *  type = "string"
 * )
 * @Param(
 *  name = "regex",
 *  description = "A regular expressions to validate the header value against.",
 *  type = "string"
 * )
 */
class HttpHeaderRegex extends Http
{

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {
        $regex = $sandbox->getParameter('regex');
        $regex = "/$regex/";
        $res = $this->getHttpResponse($sandbox);
        $header = $sandbox->getParameter('header');

        if (!$res->hasHeader($header)) {
            return false;
        }
        $headers = $res->getHeader($header);
        return preg_match($regex, $headers[0]);
    }
}
