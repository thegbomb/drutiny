<?php

namespace Drutiny\PolicySource;

use Drutiny\Api;
use Drutiny\Cache;
use Drutiny\Config;
use Drutiny\Container;
use Drutiny\Policy;
use Drutiny\Policy\Dependency;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Finder\Finder;

class LocalFs implements PolicySourceInterface
{
    protected $cache;
    protected $finder;

    public function __construct(CacheInterface $cache, Finder $finder)
    {
        $this->cache = $cache;
        $this->finder = $finder->files()->in('.');
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
        return $this->cache->get('localfs.policies', function ($item) {
            $finder = $this->finder->name('*.policy.yml');
            $list = [];
            foreach ($finder as $file) {
                $policy = Yaml::parse($file->getContents());
                $policy['uuid'] = $file->getPathname();
                $list[$policy['name']] = $policy;
            }
            return $list;
        });
    }

  /**
   * {@inheritdoc}
   */
    public function load(array $definition)
    {
        $policy = new Policy();
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
