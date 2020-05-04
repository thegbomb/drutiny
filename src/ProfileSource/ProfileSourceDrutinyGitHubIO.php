<?php

namespace Drutiny\ProfileSource;

use Drutiny\Api;
use Drutiny\Profile;
use Drutiny\Report\Format;

class ProfileSourceDrutinyGitHubIO implements ProfileSourceInterface {

  protected $api;

  public function __construct(Api $api) {
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
    foreach ($this->api->getProfileList() as $listedPolicy) {
      $list[$listedPolicy['name']] = $listedPolicy;
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function load(array $definition)
  {
    $endpoint = str_replace('{baseUri}/api/', '', $definition['_links']['self']['href']);
    $info = json_decode($this->api->getClient()->get($endpoint)->getBody(), TRUE);
    $info['filepath'] = $this->api::BaseUrl . $endpoint;

    $profile = new Profile();
    $profile->setTitle($info['title'])
            ->setName($info['name'])
            ->setFilepath($info['filepath']);

    if (isset($info['description'])) {
      $profile->setDescription($info['description']);
    }

    if (isset($info['policies'])) {
      $v21_keys = ['parameters', 'severity'];
      foreach ($info['policies'] as $name => $metadata) {
        // Check for v2.0.x style profiles.
        if (!empty($metadata) && !count(array_intersect($v21_keys, array_keys($metadata)))) {
          throw new \Exception("{$info['title']} is a v2.0.x profile. Please upgrade $filepath to v2.2.x schema.");
        }
        $weight = array_search($name, array_keys($info['policies']));
        $profile->addPolicyDefinition(PolicyDefinition::createFromProfile($name, $weight, $metadata));
      }
    }

    if (isset($info['excluded_policies']) && is_array($info['excluded_policies'])) {
      $profile->addExcludedPolicies($info['excluded_policies']);
    }

    if (isset($info['include'])) {
      foreach ($info['include'] as $name) {
        $include = ProfileSource::loadProfileByName($name);
        $profile->addInclude($include);
      }
    }

    if (isset($info['format'])) {
      foreach ($info['format'] as $format => $options) {
        $profile->addFormatOptions(Format::create($format, $options));
      }
    }
    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight()
  {
    return -100;
  }
}
