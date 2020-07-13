<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Drutiny\PolicyFactory;
use Drutiny\LanguageManager;

/**
 *
 */
class PolicyShowCommand extends Command
{
  protected $policyFactory;
  protected $languageManager;

  public function __construct(PolicyFactory $factory, LanguageManager $languageManager)
  {
      $this->policyFactory = $factory;
      $this->languageManager = $languageManager;
      parent::__construct();
  }

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
        )
        ->addOption(
            'language',
            '',
            InputOption::VALUE_OPTIONAL,
            'Define which language to use for policies and profiles. Defaults to English (en).',
            'en'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set global language used by policy/profile sources.
        $this->languageManager->setLanguage($input->getOption('language'));

        $policy = $this->policyFactory->loadPolicyByName($input->getArgument('policy'));
        $output->write(Yaml::dump($policy->export(), 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return 0;
    }
}
