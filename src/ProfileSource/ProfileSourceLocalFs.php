<?php

namespace Drutiny\ProfileSource;

use Drutiny\Profile;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Drutiny\Profile\PolicyDefinition;

class ProfileSourceLocalFs implements ProfileSourceInterface
{

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
      $name = str_replace('.profile.yml', '', pathinfo($filepath, PATHINFO_BASENAME));

      $profile = new Profile();
      $profile->setTitle($info['title'])
          ->setName($name)
          ->setFilepath($filepath);

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
              $profile->addFormatOptions($format, $options);
          }
      }
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
