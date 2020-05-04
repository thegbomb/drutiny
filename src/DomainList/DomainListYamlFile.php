<?php

namespace Drutiny\DomainList;

use Symfony\Component\Yaml\Yaml;

/**
 * Load domain lists from a yaml file.
 *
 * YAML file should use this schema:
 * domains:
 *   - mysite.com
 *   - example.com
 */
class DomainListYamlFile extends AbstractDomainList
{

    protected $filepath;

  /**
   * {@inheritdoc}
   */
    public function configure()
    {
        $this->addOption('filepath', 'Filepath to the YAML file containing the domains');
    }

  /**
   * @return array list of domains.
   */
    public function getDomains(array $options = [])
    {
        $config = Yaml::parseFile($options['filepath']);
        return $config['domains'];
    }
}
