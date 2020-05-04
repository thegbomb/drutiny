<?php

namespace Drutiny\Http\Middleware;

use Drutiny\Http\MiddlewareInterface;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Drutiny\Console\Verbosity;
use Drutiny\ImmuntableConfig;

class Logger implements MiddlewareInterface {
  protected $config;
  protected $logger;
  protected $verbosity;

  /**
   * @param $container ContainerInterface
   */
  public function __construct(Verbosity $verbosity, ConsoleLogger $logger, ImmuntableConfig $config) {
    $this->config = $config->getConfig('http');
    $this->logger = $logger;
    $this->verbosity = $verbosity;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(RequestInterface $request) {
    $message_format = " HTTP Request\n\n{req_headers}\n\n{res_headers}";

    // Add additional information in higher verbosity.
    $verbosity = $this->verbosity->get();
    if ($verbosity <= OutputInterface::VERBOSITY_VERY_VERBOSE) {
      $message_format = " {code} {phrase} {uri} {error}";
    }

    // Logging HTTP Requests.
    return Middleware::log(
      $this->logger,
      new MessageFormatter($message_format)
    );
  }
}
