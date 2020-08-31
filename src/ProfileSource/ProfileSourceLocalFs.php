<?php

namespace Drutiny\ProfileSource;

use Drutiny\LanguageManager;
use Drutiny\Profile;
use Drutiny\Profile\PolicyDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class ProfileSourceLocalFs implements ProfileSourceInterface
{
    protected $container;
    protected $cache;
    protected $finder;

    public function __construct(CacheInterface $cache, Finder $finder, ContainerInterface $container)
    {
        $this->cache = $cache;
        $this->finder = $finder
          ->files()
          ->in(DRUTINY_LIB)
          ->name('*.profile.yml');

        try {
          $this->finder->in($container->getParameter('drutiny_config_dir'));
        }
        catch (DirectoryNotFoundException $e) {
          // Ignore not finding an existing config dir.
        }

        $this->container = $container;
    }

  /**
   * {@inheritdoc}
   */
    public function getName()
    {
        return 'localfs';
    }

  /**
   * {@inheritdoc}
   */
    public function getList(LanguageManager $languageManager)
    {
        $list = [];
        foreach ($this->finder as $file) {
            $filename = $file->getRealPath();
            $name = str_replace('.profile.yml', '', pathinfo($filename, PATHINFO_BASENAME));
            $profile = Yaml::parse($file->getContents());
            $profile['language'] = $profile['language'] ?? $languageManager->getDefaultLanguage();

            if ($languageManager->getCurrentLanguage() != $profile['language']) {
              continue;
            }

            $profile['filepath'] = $filename;
            $profile['name'] = $name;
            unset($profile['format']);
            $list[$name] = $profile;
        }
        return $list;
    }

  /**
   * {@inheritdoc}
   */
    public function load(array $definition)
    {
      $filepath = $definition['filepath'];

      $info = Yaml::parseFile($filepath);
      $info['name'] = str_replace('.profile.yml', '', pathinfo($filepath, PATHINFO_BASENAME));
      $info['uuid'] = $filepath;

      $profile = $this->container->get('profile');
      $profile->setProperties($info);

      return $profile;
    }

  /**
   * {@inheritdoc}
   */
    public function getWeight()
    {
        return -10;
    }
}
