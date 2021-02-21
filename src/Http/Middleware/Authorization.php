<?php

namespace Drutiny\Http\Middleware;

use Drutiny\Http\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class Authorization implements MiddlewareInterface
{
    protected $config;
    protected $logger;

  /**
   * @param $container ContainerInterface
   * @param $config @config service.
   */
    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get('credentials')->load('http')->authorization ?? [];
        $this->logger = $container->get('logger');
    }

  /**
   * {@inheritdoc}
   */
    public function handle(RequestInterface $request)
    {
        $uri = (string) $request->getUri();
        $host = parse_url($uri, PHP_URL_HOST);

      // Do not apply if not supported for the request host.
        if (!isset($this->config[$host])) {
            return $request;
        }

      // Do not apply if path isset and doesn't match.
        $path = $this->config[$host]['path'] ?? false;
        if ($path && strpos(parse_url($uri, PHP_URL_PATH), $path) === 0) {
            return $request;
        }

        $username = $this->config[$host]['username'] ?? '';
        $password = $this->config[$host]['password'] ?? '';
        $header_value = 'Basic ' . base64_encode($username .':'. $password);

        $this->logger->debug("Using HTTP Authorization for $host: $header_value.");

        return $request->withHeader('Authorization', $header_value);
    }
}
