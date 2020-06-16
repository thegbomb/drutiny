<?php

namespace Drutiny\Http\Middleware;

use Drutiny\Console\Verbosity;
use Drutiny\Http\MiddlewareInterface;
use Drutiny\ConfigFile;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Logger implements MiddlewareInterface
{
    protected $config;
    protected $logger;
    protected $verbosity;

  /**
   * @param $container ContainerInterface
   */
    public function __construct(Verbosity $verbosity, LoggerInterface $logger, ConfigFile $config)
    {
        $this->config = $config->setNamespace('http');
        $this->logger = $logger;
        $this->verbosity = $verbosity;
    }

  /**
   * {@inheritdoc}
   */
    public function handle(RequestInterface $request)
    {
        $message_format = " HTTP Request\n\n{req_headers}\n\n{req_body}";

      // Add additional information in higher verbosity.
        $verbosity = $this->verbosity->get();
        if ($verbosity <= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $message_format = " {code} {phrase} {uri} {error}";
        }

        $formatter = new MessageFormatter($message_format);
        $this->logger->info($formatter->format($request));

      // Logging HTTP Requests.
        return $request;
    }
}
