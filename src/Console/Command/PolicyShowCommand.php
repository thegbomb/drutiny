<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Drutiny\PolicyFactory;

/**
 *
 */
class PolicyShowCommand extends Command
{

  public function __construct(PolicyFactory $factory)
  {
      $this->policyFactory = $factory;
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
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $policy = $this->policyFactory->loadPolicyByName($input->getArgument('policy'));
        $output->write(Yaml::dump($policy->export(), 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return 0;
    }
}
