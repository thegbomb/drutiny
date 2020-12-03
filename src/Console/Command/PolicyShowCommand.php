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
        )
        ->addOption(
            'backward-compatibility',
            'b',
            InputOption::VALUE_NONE,
            'Render templates in backwards compatibility mode.'
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
        if (!$input->getOption('backward-compatibility')) {
            foreach (['success', 'failure', 'warning'] as $field) {
                if (empty($export[$field])) continue;

                $export[$field] = $this->prefixTemplate($export[$field]);

                // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
                $export[$field] = $this->preMapDrutiny2Variables($export[$field]);

                // Convert from Mustache (supported in Drutiny 2.x) over to twig syntax.
                $export[$field] = $this->convertMustache2TwigSyntax($export[$field]);

                // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
                $export[$field] = $this->mapDrutiny2toDrutiny3variables($export[$field]);
            }
        }
        //var_dump($export['success']);
        $output->write(Yaml::dump($export, 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return 0;
    }
}
