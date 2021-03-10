<?php

namespace Drutiny\ProfileSource;

use Drutiny\LanguageManager;
use Drutiny\Profile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class ProfileSourceLocalFs extends ProfileSource
{
    protected int $weight = -10;
    protected Finder $finder;

    public function __construct(Finder $finder, ContainerInterface $container)
    {
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

        parent::__construct($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getName():string
    {
        return 'localfs';
    }

    /**
     * {@inheritdoc}
     */
    public function getList(LanguageManager $languageManager):array
    {
        $list = [];
        foreach ($this->finder as $file) {
            $filename = $file->getPathname();
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
    public function load(array $definition):Profile
    {
      $filepath = $definition['filepath'];

      $info = Yaml::parse(file_get_contents($filepath));
      $info['name'] = str_replace('.profile.yml', '', pathinfo($filepath, PATHINFO_BASENAME));
      $info['uuid'] = $filepath;

      return parent::load($info);
    }
}
