<?php

namespace Drutiny\ProfileSource;

use Drutiny\Api;
use Drutiny\Profile;
use Drutiny\Report\Format;
use Drutiny\LanguageManager;
use Drutiny\ProfileFactory;
use Drutiny\Profile\PolicyDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ProfileSourceDrutinyGitHubIO implements ProfileSourceInterface
{

    protected $api;
    protected $profileFactory;
    protected $container;

    public function __construct(Api $api, ProfileFactory $profileFactory, ContainerInterface $container)
    {
        $this->api = $api;
        $this->profileFactory = $profileFactory;
        $this->container = $container;
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
    public function getList(LanguageManager $languageManager)
    {
        $list = [];
        foreach ($this->api->getProfileList() as $listedPolicy) {
            $listedPolicy['language'] = $listedPolicy['language'] ?? $languageManager->getDefaultLanguage();

            if ($languageManager->getCurrentLanguage() != $listedPolicy['language']) {
              continue;
            }

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
        $info = json_decode($this->api->getClient()->get($endpoint)->getBody(), true);
        $info['uuid'] = $this->api::BaseUrl . $endpoint;

        $profile = $this->container->get('profile');
        $profile->setProperties($info);

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
