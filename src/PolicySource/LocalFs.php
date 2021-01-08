<?php

namespace Drutiny\PolicySource;

use Drutiny\Policy;
use Drutiny\Policy\Dependency;
use Drutiny\LanguageManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

class LocalFs implements PolicySourceInterface
{
    protected $cache;
    protected $finder;

    public function __construct(CacheInterface $cache, Finder $finder, ContainerInterface $container)
    {
        // Ensure the policy directory is available.
        $fs = $container->getParameter('policy.library.fs');
        is_dir($fs) || mkdir($fs, 0744, true);

        $this->cache = $cache;
        $this->finder = $finder
          ->files()
          ->in([$container->getParameter('policy.library.fs'), DRUTINY_LIB])
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
        //return $this->cache->get('localfs.policies.'.$lang_code, function ($item) use ($languageManager) {
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
        //});
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
