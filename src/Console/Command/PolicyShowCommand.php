<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Drutiny\PolicyFactory;

/**
 *
 */
class PolicyShowCommand extends DrutinyBaseCommand
{
  use LanguageCommandTrait;
  protected $policyFactory;
  protected $languageManager;

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('policy:show')
        ->setDescription('Show a policy definition.')
        ->addArgument(
            'policy',
            InputArgument::REQUIRED,
            'The name of the profile to show.'
        );
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLanguage($input);
        $policy = $this->getPolicyFactory()->loadPolicyByName($input->getArgument('policy'));
        $export = $policy->export();

        foreach (['description', 'success', 'remediation', 'failure', 'warning'] as $field) {
          if (isset($export[$field])) {
            $export[$field] = str_replace("\r", '', $export[$field]);
          }
        }

        $output->write(Yaml::dump($export, 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return 0;
    }
}
