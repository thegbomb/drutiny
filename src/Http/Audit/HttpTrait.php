<?php

namespace Drutiny\Http\Audit;

use Drutiny\Sandbox\Sandbox;
use Drutiny\Http\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

trait HttpTrait
{

    protected function getHttpResponse(Sandbox $sandbox)
    {
        $url = $sandbox->getParameter('url', $uri = $sandbox->getTarget()->uri());

      // This allows policies to specify urls that still contain a domain.
        $url = strtr($url, [
        ':uri' => $uri,
        ]);

        if ($sandbox->getParameter('force_ssl', false)) {
            $url = strtr($url, [
            'http://' => 'https://',
            ]);
        }

        $sandbox->setParameter('url', $url);

        $method = $sandbox->getParameter('method', 'GET');

        $sandbox->logger()->info(__CLASS__ . ': ' . $method . ' ' . $url);
        $options = $sandbox->getParameter('options', []);

        $status_code = $sandbox->getParameter('status_code');

        $handler = HandlerStack::create();

      // Warm remote caches.
        $client = new Client([
        'cache' => $sandbox->getParameter('use_cache', true),
        'handler' => $handler,
        ]);

        $handler->before('cache', Middleware::mapRequest(function (RequestInterface $request) use ($sandbox) {
            $sandbox->setParameter('req_headers', $request->getHeaders());
            return $request;
        }));

        return $client->request($method, $url, $options);
    }
}
