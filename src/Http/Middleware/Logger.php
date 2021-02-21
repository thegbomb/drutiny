<?php

namespace Drutiny\Http\Middleware;

use Drutiny\Http\MiddlewareInterface;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Logger implements MiddlewareInterface
{
    protected $config;
    protected $logger;

  /**
   * @param $container ContainerInterface
   */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

  /**
   * {@inheritdoc}
   */
    public function handle(RequestInterface $request)
    {
        $message_format = " {code} {phrase} {uri} {error}\n\nHTTP Request\n\n{req_headers}\n\n{req_body}";
        $formatter = new MessageFormatter($message_format);
        $this->logger->debug($formatter->format($request));

      // Logging HTTP Requests.
        return $request;
    }
}
