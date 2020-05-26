<?php

namespace Drutiny\Http\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Audit\AuditValidationException;

/**
 *
 * @Param(
 *  name = "host",
 *  description = "The domain name to connect to.",
 *  type = "string",
 *  default = false
 * )
 * @Param(
 *  name = "port",
 *  description = "The SSL port to connect to",
 *  type = "integer",
 *  default = 443
 * )
 * @Param(
 *  name = "expression",
 *  type = "string",
 *  default = "true",
 *  description = "The expression language to evaludate. See https://symfony.com/doc/current/components/expression_language/syntax.html"
 * )
 * @Token(
 *  name = "cert",
 *  description = "An multidimension array of representing the certificate info",
 *  type = "array"
 * )
 */
class SslAssertion extends AbstractAnalysis
{

    protected function requireOpenSslExtension(Sandbox $sandbox)
    {
        return function_exists('openssl_x509_parse');
    }

  /**
   *
   */
    public function gather(Sandbox $sandbox)
    {
        if (!$url = $sandbox->getParameter('host')) {
            $url = $sandbox->getTarget()->uri();
        }

        $host = (strpos($url, '://') !== false) ? parse_url($url, PHP_URL_HOST) : $url;
        $sandbox->setParameter('host', $host);
        $port = $sandbox->getParameter('port');

        $url = 'ssl://' . $host . ':' . $port;

        $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        if (!$client = @stream_socket_client($url, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context)) {
            throw new AuditValidationException("$host did not accept an SSL connection on port $port");
        }

        $cert = stream_context_get_params($client);
        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

        $certinfo['issued'] = date('Y-m-d H:i:s', $certinfo['validFrom_time_t']);

        $sandbox->setParameter('cert', $certinfo);
    }
}
