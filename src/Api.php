<?php

namespace Drutiny;

use Drutiny\Http\Client;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\Console\Logger\ConsoleLogger;

class Api {
  const BaseUrl = 'https://drutiny.github.io/api/v2/en/';
  protected $httpFactory;
  protected $logger;

  public function __construct(Client $http_factory, ConsoleLogger $logger) {
    $this->httpFactory = $http_factory;
    $this->logger = $logger;
  }

  public function getClient()
  {
    return $this->httpFactory->create([
      'base_uri' => self::BaseUrl,
      'headers' => [
        'User-Agent' => 'drutiny/2.2.x',
        'Accept' => 'application/json',
        'Accept-Encoding' => 'gzip'
      ],
      'decode_content' => 'gzip',
      'allow_redirects' => FALSE,
      'connect_timeout' => 10,
      'timeout' => 300,
    ]);
  }

  public function getPolicyList()
  {
    try {
      return json_decode($this->getClient()->get('policy/index.json')->getBody(), TRUE);
    }
    catch (ConnectException $e) {
      $this->logger->warning($e->getMessage());
      return [];
    }
  }

  public function getProfileList()
  {
    try {
      return json_decode($this->getClient()->get('profile/index.json')->getBody(), TRUE);
    }
    catch (ConnectException $e) {
      $this->logger->warning($e->getMessage());
      return [];
    }
  }
}
?>
