<?php

namespace Drutiny\Http\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 *
 * @Param(
 *  name = "header",
 *  description = "The HTTP header to check the value of.",
 *  type = "string"
 * )
 * @Token(
 *  name = "header_value",
 *  description = "The value to check against.",
 *  type = "string"
 * )
 */
class HttpHeaderNotExists extends HttpHeaderExists
{

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {
        return !parent::audit($sandbox);
    }
}
