<?php

namespace Drutiny;

use Drutiny\ProfileSource\ProfileSourceInterface;
use Drutiny\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProfileFactory
{

    use ContainerAwareTrait;

    protected $cache;
    protected $languageManager;

    public function __construct(ContainerInterface $container, CacheInterface $cache, LanguageManager $languageManager)
    {
        $this->setContainer($container);
        $this->cache = $cache;
        $this->languageManager = $languageManager;
    }

  /**
   * Load policy by name.
   *
   * @param $name string
   */
    public function loadProfileByName($name)
    {
        $list = $this->getProfileList();

        if (!isset($list[$name])) {
            throw new \Exception("No such profile found: $name.");
        }
        $definition = $list[$name];
        return $this->getSource($definition['source'])->load($definition);
    }

  /**
   * Acquire a list of available policies.
   *
   * @return array of policy information arrays.
   */
    public function getProfileList():array
    {
        $lang_code = $this->languageManager->getCurrentLanguage();
        return $this->cache->get('profile.list'.$lang_code, function (ItemInterface $item) {
          // $item->expiresAfter(0);
            $list = [];

            foreach ($this->getSources() as $source) {
                foreach ($source->getList($this->languageManager) as $name => $item) {
                    $item['source'] = $source->getName();
                    $list[$name] = $item;
                }
            }

            return $list;
        });
    }

  /**
   * Load the sources that provide policies.
   *
   * @return array of PolicySourceInterface objects.
   */
    public function getSources():array
    {
        $sources = [];
        foreach ($this->container->findTaggedServiceIds('profile.source') as $id => $info) {
            $sources[$id] = $this->container->get($id);
        }

      // If multiple sources provide the same policy by name, then the policy from
      // the first source in the list will by used.
        uasort($sources, function ($a, $b) {
            if ($a->getWeight() == $b->getWeight()) {
                return 0;
            }
            return $a->getWeight() > $b->getWeight() ? 1 : -1;
        });

        return $sources;
    }

  /**
   * Load a single source.
   */
    public function getSource($name):ProfileSourceInterface
    {
        foreach ($this->getSources() as $class => $source) {
            if ($source->getName() == $name) {
                return $source;
            }
        }
        throw new \Exception("No such source found: $name.");
    }
}
