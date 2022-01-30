<?php

namespace Drutiny\Console\Command;

use Drutiny\Report\FormatInterface;
use Drutiny\DomainSource;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

/**
 *
 */
trait DomainSourceCommandTrait
{
  /**
   * @inheritdoc
   */
    protected function configureDomainSource()
    {
        $this
        ->addOption(
            'domain-source',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Use a domain source to preload uri options. Defaults to yaml filepath.',
            'yaml'
        )->addOption(
            'domain-source-blacklist',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Exclude domains that match this regex filter',
            []
        )
        ->addOption(
            'domain-source-whitelist',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Exclude domains that don\'t match this regex filter',
            []
        );

        // Build a way for the command line to specify the options to derive
        // domains from their sources. NOTE: we cannot acquire the container
        // from the application object at this time as the application is
        // yet to be associated with the command.
        foreach (\drutiny()->get('domain.source')->getSources() as $driver => $properties) {
            foreach ($properties as $name => $description) {
                $this->addOption(
                    'domain-source-' . $driver . '-' . $name,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    $description
                );
            }
        }
    }

    protected function parseDomainSourceOptions(InputInterface $input):array
    {
      // Load additional uris from domain-source
        $sources = [];
        foreach ($input->getOptions() as $name => $value) {
            if ($value === null) {
                continue;
            }
            if (strpos($name, 'domain-source-') === false) {
                continue;
            }
            $param = str_replace('domain-source-', '', $name);
            if (strpos($param, '-') === false) {
                continue;
            }
            list($source, $name) = explode('-', $param, 2);
            $sources[$source][$name] = $value;
        }
        return $sources;
    }

    /**
     * Determine a default filepath.
     */
      protected function getDomainSource():DomainSource
      {
          return $this->getContainer()->get('domain.source');
      }
}
