<?php

namespace Drutiny;

use Drutiny\PolicySource\PolicySourceInterface;
use Drutiny\Policy\UnavailablePolicyException;
use Drutiny\Policy\UnknownPolicyException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class PolicyFactory
{

    use ContainerAwareTrait;

    protected $cache;

    public function __construct(ContainerInterface $container, CacheInterface $cache, LoggerInterface $logger)
    {
        $this->setContainer($container);
        $this->cache = $cache;
        $this->logger = $logger;
    }

  /**
   * Load policy by name.
   *
   * @param $name string
   */
    public function loadPolicyByName($name)
    {
        $list = $this->getPolicyList();

        if (!isset($list[$name])) {
            $list = $this->getPolicyList(true);
            if (!isset($list[$name])) {
                throw new UnknownPolicyException("$name does not exist.");
            }
            throw new UnavailablePolicyException("$name requires {$list[$name]['class']} but is not available in this environment.");
        }
        $definition = $list[$name];

        try {
            return $this->getSource($definition['source'])->load($definition);
        } catch (\InvalidArgumentException $e) {
            $this->container->get('logger')->warning($e->getMessage());
            throw new UnavailablePolicyException("$name requires {$list[$name]['class']} but is not available in this environment.");
        }
    }

  /**
   * Acquire a list of available policies.
   *
   * @return array of policy information arrays.
   */
    public function getPolicyList($include_invalid = false)
    {
        static $policy_list, $available_list;

        if ($include_invalid && !empty($policy_list)) {
            return $policy_list;
        }

        if (!empty($available_list)) {
            return $available_list;
        }
        $policy_list = $this->cache->get('policy.list', function ($item) {
            $list = [];
            foreach ($this->getSources() as $source) {
                try {
                    $items = $source->getList();
                    $this->container->get('logger')->notice($source->getName() . " has " . count($items) . " polices.");
                    foreach ($items as $name => $item) {
                        $item['source'] = $source->getName();
                        $list[$name] = $item;
                    }
                } catch (\Exception $e) {
                    $this->container->get('logger')->error(strtr("Failed to load policies from source: @name: @error", [
                    '@name' => $source->getName(),
                    '@error' => $e->getMessage(),
                    ]));
                }
            }
            return $list;
        });

        if ($include_invalid) {
            return $policy_list;
        }

        $available_list = array_filter($policy_list, function ($listedPolicy) {
            try {
                $this->container->get($listedPolicy['class']);
                return true;
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
                return false;
            }
        });
        return $available_list;
    }

  /**
   * Load the sources that provide policies.
   *
   * @return array of PolicySourceInterface objects.
   */
    public function getSources()
    {
        $sources = [];
        foreach ($this->container->findTaggedServiceIds('policy.source') as $id => $info) {
            $sources[] = $this->container->get($id);
        }

        // If multiple sources provide the same policy by name, then the policy from
        // the first source in the list will by used.
        usort($sources, function ($a, $b) {
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
    public function getSource($name):PolicySourceInterface
    {
        foreach ($this->getSources() as $class => $source) {
            if ($source->getName() == $name) {
                return $source;
            }
        }
        throw new \Exception("No such source found: $name.");
    }
}
