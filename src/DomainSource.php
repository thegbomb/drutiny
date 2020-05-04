<?php

namespace Drutiny;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Drutiny\DomainList\DomainListRegistry;
use Drutiny\Target\Registry as TargetRegistry;

class DomainSource {
  use ContainerAwareTrait;

  protected $cache;

  public function __construct(ContainerInterface $container, CacheInterface $cache) {
    $this->setContainer($container);
    $this->cache = $cache;
  }

  public function getSources()
  {
    return $this->cache->get('domain_list.sources', function ($item) {
      $sources = [];
      foreach ($this->container->findTaggedServiceIds('domain_list') as $id => $info) {
        list($ns, $driver) = explode('.', $id, 2);
        $sources[$driver] = $this->container->get($id)->getOptionsDefinitions();
      }
      return $sources;
    });
  }

  public function getDomains($source, array $options = []): array
  {
    return $this->container
      ->get("domain_list.$source")
      ->getDomains($options);
  }

  public function loadFromInput(InputInterface $input)
  {
    $sources = [];
    foreach ($input->getOptions() as $name => $value) {
      if (strpos($name, 'domain-source-') === FALSE) {
        continue;
      }
      list($source, $name) = explode('-', str_replace('domain-source-', '', $name), 2);
      $sources[$source][$name] = $value;
    }

    $domains = [];

    foreach ($sources as $source => $options) {
      $domains += $this->container
        ->get("domain_list.$source")
        ->getDomains($options);
    }

    $whitelist = $input->getOption('domain-source-whitelist');
    $blacklist = $input->getOption('domain-source-blacklist');

    // Filter domains by whitelist and blacklist.
    return array_filter($domains, function ($domain) use ($whitelist, $blacklist) {
      // Whitelist priority.
      if (!empty($whitelist)) {
        foreach ($whitelist as $regex) {
          if (preg_match("/$regex/", $domain)) {
            return TRUE;
          }
        }
        // Did not pass the whitelist.
        return FALSE;
      }
      if (!empty($blacklist)) {
        foreach ($blacklist as $regex) {
          if (preg_match("/$regex/", $domain)) {
            return FALSE;
          }
        }
      }
      return TRUE;
    });
  }
}

 ?>
