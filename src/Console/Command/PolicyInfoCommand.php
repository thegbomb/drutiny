<?php

namespace Drutiny\Console\Command;

use Drutiny\PolicyFactory;
use Drutiny\ProfileFactory;
use Drutiny\LanguageManager;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

/**
 *
 */
class PolicyInfoCommand extends DrutinyBaseCommand
{

  use LanguageCommandTrait;

  protected $policyFactory;
  protected $profileFactory;
  protected $twig;

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('policy:info')
        ->setDescription('Show information about a specific policy.')
        ->addArgument(
            'policy',
            InputArgument::REQUIRED,
            'The name of the check to run.'
        );
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set global language used by policy/profile sources.
        $this->initLanguage($input);

        $policy = $this->getPolicyFactory()->loadPolicyByName($input->getArgument('policy'));

        $template = $this->getContainer()->get('twig')->load('docs/policy.md.twig');
        $markdown = $template->render($policy->export());

        $formatted_output = Renderer::createFromMarkdown($markdown);
        $output->writeln((string) $formatted_output);


        $profiles = array_map(function ($profile) {
          return $this->getProfileFactory()->loadProfileByName($profile['name']);
        }, $this->getProfileFactory()->getProfileList());

        $io = new SymfonyStyle($input, $output);
        $io->title('Profiles');
        $profiles = array_filter($profiles, function ($profile) use ($policy) {
          $list = array_keys($profile->getAllPolicyDefinitions());
          return in_array($policy->name, $list);
        });
        $io->listing(array_map(function ($profile) {
          return $profile->name;
        }, $profiles));
        return 0;
    }
}
