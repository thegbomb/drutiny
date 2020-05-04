<?php

namespace Drutiny\Http\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

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
 * @Token(
 *  name = "request_error",
 *  description = "If the request failed, this token will contain the error message.",
 *  type = "string"
 * )
 */
class HttpHeaderExists extends Http
{

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {
        try {
            $res = $this->getHttpResponse($sandbox);
            if ($has_header = $res->hasHeader($sandbox->getParameter('header'))) {
                $headers = $res->getHeader($sandbox->getParameter('header'));
                $sandbox->setParameter('header_value', $headers[0]);
            }
            return $has_header;
        } catch (RequestException $e) {
            $sandbox->logger()->error($e->getMessage());
            $sandbox->setParameter('request_error', $e->getMessage());
        }
        return false;
    }
}
