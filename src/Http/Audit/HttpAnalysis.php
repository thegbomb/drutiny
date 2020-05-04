<?php

namespace Drutiny\Http\Audit;

use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Sandbox\Sandbox;
use Psr\Http\Message\ResponseInterface;
use Drutiny\Annotation\Param;

/**
 *
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "not_applicable",
 *  type = "string",
 *  default = "false",
 *  description = "The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Param(
 *  name = "send_warming_request",
 *  type = "boolean",
 *  default = false,
 *  description = "Send a warming request and store headers into cold_headers parameter."
 * )
 * @Param(
 *  name = "use_cache",
 *  type = "boolean",
 *  default = true,
 *  description = "Indicator if Guzzle client should use cache middleware."
 * )
 * @Param(
 *  name = "options",
 *  type = "array",
 *  default = {},
 *  description = "An options array passed to the Guzzle client request method."
 * )
 */
class HttpAnalysis extends AbstractAnalysis
{
    use HttpTrait;

    protected function gather(Sandbox $sandbox)
    {
        $use_cache = $sandbox->getParameter('use_cache', false);
      // For checking caching functionality, add a listener
      // to pre-warm the origin.
        if ($sandbox->setParameter('send_warming_request', false)) {
            $sandbox->setParameter('use_cache', false);
            $response = $this->getHttpResponse($sandbox);
            $sandbox->setParameter('cold_headers', $this->gatherHeaders($response));
        }

        $sandbox->setParameter('use_cache', $use_cache);
        $response = $this->getHttpResponse($sandbox);

      // Maintain for backwards compatibility.
        $sandbox->setParameter('headers', $this->gatherHeaders($response));

        $sandbox->setParameter('res_headers', $response->getHeaders());
    }

    protected function gatherHeaders(ResponseInterface $response)
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $directives = array_map('trim', explode(',', $value));
                foreach ($directives as $directive) {
                    list($flag, $flag_value) = strpos($directive, '=') ? explode('=', $directive) : [$directive, null];

                    $headers[strtolower($name)][strtolower($flag)] = is_null($flag_value) ?: $flag_value;
                }
            }
        }

        foreach ($headers as $name => $values) {
            if (count($values) == 1 && current($values) === true) {
                $headers[$name] = key($values);
            }
        }

        return $headers;
    }
}
