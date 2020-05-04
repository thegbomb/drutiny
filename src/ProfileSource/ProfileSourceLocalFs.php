<?php

namespace Drutiny\ProfileSource;

use Drutiny\Profile;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class ProfileSourceLocalFs implements ProfileSourceInterface {

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
    return Profile::loadFromFile($definition['filepath']);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight()
  {
    return -10;
  }
}
