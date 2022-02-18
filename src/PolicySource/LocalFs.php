<?php

namespace Drutiny\PolicySource;

use Drutiny\Policy;
use Drutiny\Policy\Dependency;
use Drutiny\LanguageManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

class LocalFs implements PolicySourceInterface
{
    protected $finder;

    public function __construct(Finder $finder, ContainerInterface $container)
    {
        // Ensure the policy directory is available.
        $fs = (array) $container->getParameter('policy.library.fs');
        $fs[] = DRUTINY_LIB;

        $fs = array_filter($fs, fn($p) => is_dir($p) || mkdir($p, 0744, true));

        $this->finder = $finder
          ->files()
          ->in($fs)
          ->name('*.policy.yml');
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
        $lang_code = $languageManager->getCurrentLanguage();
        $list = [];
        foreach ($this->finder as $file) {
            $policy = Yaml::parse($file->getContents());
            $policy['uuid'] = md5($file->getPathname());
            $policy['filepath'] = $file->getPathname();
            $policy['language'] = $policy['language'] ?? $languageManager->getDefaultLanguage();

            if ($policy['language'] != $languageManager->getCurrentLanguage()) {
                continue;
            }
            $list[$policy['name']] = $policy;
        }
        return $list;
    }

  /**
   * {@inheritdoc}
   */
    public function load(array $definition)
    {
        $policy = new Policy();

        // Load from disk rather than cache.
        if (file_exists($definition['filepath'])) {
          $uuid = $definition['uuid'];
          $definition = Yaml::parseFile($definition['filepath']);
          $definition['uuid'] = $uuid;
        }
        unset($definition['source']);
        // Convert parameters to remove default key.
        if (!empty($definition['parameters'])) {
          foreach ($definition['parameters'] as &$value) {
            if (isset($value['default'])) {
              $value = $value['default'];
            }
          }
        }

        if (isset($definition['depends'])) {
            foreach ($definition['depends'] as &$dependency) {
                $dependency = !is_string($dependency) ? $dependency : [
                'expression' => sprintf("policy('%s') == 'success'", $dependency),
                'on_fail' => Dependency::ON_FAIL_REPORT_ONLY
                ];
            }
        }
        return $policy->setProperties($definition);
    }

  /**
   * {@inheritdoc}
   */
    public function getWeight()
    {
        return -10;
    }
}
