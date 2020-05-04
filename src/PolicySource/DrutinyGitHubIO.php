<?php

namespace Drutiny\PolicySource;

use Drutiny\Api;
use Drutiny\Policy;
use Drutiny\Policy\Dependency;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Contracts\Cache\CacheInterface;

class DrutinyGitHubIO implements PolicySourceInterface {
  use ContainerAwareTrait;

  protected $cache;
  protected $api;

  public function __construct(ContainerInterface $container, CacheInterface $cache, Api $api) {
    $this->setContainer($container);
    $this->cache = $cache;
    $this->api = $api;
  }

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return 'drutiny.github.io';
  }

  /**
   * {@inheritdoc}
   */
  public function getList()
  {
    $list = [];
    foreach ($this->api->getPolicyList() as $listedPolicy) {
      $listedPolicy['filepath'] = Api::BaseUrl . $listedPolicy['_links']['self']['href'];
      $listedPolicy['class'] = preg_replace('/^\\\/', '', $listedPolicy['class']);
      $list[$listedPolicy['name']] = $listedPolicy;
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition)
  {
    $cid = 'drutiny.github.io.policy.'.$definition['signature'];

    $data = $this->cache->get($cid, function ($item) use ($definition) {
      $item->expiresAt(new \DateTime('+1 month'));

      $endpoint = str_replace(parse_url(Api::BaseUrl, PHP_URL_PATH), '', $definition['_links']['self']['href']);
      $policyData = json_decode($this->api->getClient()->get($endpoint)->getBody(), TRUE);
      $policyData['filepath'] = $definition['_links']['self']['href'];

      return $policyData;
    });

    if (isset($data['depends'])) {
      foreach ($data['depends'] as &$dependency) {
        $dependency = !is_string($dependency) ? $dependency : [
          'expression' => sprintf("policy('%s') == 'success'", $dependency),
          'on_fail' => Dependency::ON_FAIL_REPORT_ONLY
        ];
      }
    }

    $data['uuid'] = $data['signature'];
    unset($data['signature'], $data['filepath']);

    // Workaround to remove leading \.
    $data['class'] = preg_replace('/^\\\/', '', $data['class']);

    $policy = new Policy;
    $policy->setProperties($data);
    return $policy;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight()
  {
    return -100;
  }
}
