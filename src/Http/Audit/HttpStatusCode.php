<?php

namespace Drutiny\Http\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;

/**
 *
 * @Param(
 *  name = "status_code",
 *  description = "The expected status code from the HTTP response",
 *  type = "string",
 *  default = 200
 * )
 */
class HttpStatusCode extends Http
{

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {
        $status_code = $sandbox->getParameter('status_code', 200);
        $res = $this->getHttpResponse($sandbox);
        return $status_code == $res->getStatusCode();
    }
}
