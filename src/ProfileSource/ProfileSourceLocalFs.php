<?php

namespace Drutiny\ProfileSource;

use Drutiny\Profile;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Drutiny\Profile\PolicyDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProfileSourceLocalFs implements ProfileSourceInterface
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
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
    public function getList()
    {
        $finder = new Finder();
        $finder->files()
        ->in('.')
        ->name('*.profile.yml');

        $list = [];
        foreach ($finder as $file) {
            $filename = $file->getRealPath();
            $name = str_replace('.profile.yml', '', pathinfo($filename, PATHINFO_BASENAME));
            $profile = Yaml::parse($file->getContents());
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
