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

      // This allows policies to specify urls that still contain a domain.
        $url = $this->target['uri'];

        if ($this->getParameter('force_ssl', false)) {
            $url = strtr($url, [
            'http://' => 'https://',
            ]);
        }

        $this->set('url', $url);

        $method = $this->getParameter('method', 'GET');

        $this->logger->info(__CLASS__ . ': ' . $method . ' ' . $url);
        $options = $this->getParameter('options', []);

        $status_code = $this->getParameter('status_code');

        $handler = HandlerStack::create();

        $client = $this->container->get('http.client')->create([
          'cache' => $this->getParameter('use_cache', true),
          'handler' => $handler,
        ]);

        $handler->before('cache', Middleware::mapRequest(function (RequestInterface $request) use ($sandbox) {
            $this->set('req_headers', $request->getHeaders());
            return $request;
        }));

        return $client->request($method, $url, $options);
    }
}
