<?php

namespace Drutiny\Console\Command;

use Drutiny\PolicyFactory;
use Drutiny\LanguageManager;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

/**
 *
 */
class PolicyInfoCommand extends Command
{
  protected $policyFactory;
  protected $languageManager;
  protected $twig;


  public function __construct(PolicyFactory $factory, Environment $twig, LanguageManager $languageManager)
  {
      $this->policyFactory = $factory;
      $this->twig = $twig;
      $this->languageManager = $languageManager;
      parent::__construct();
  }

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

        $template = $this->twig->load('docs/policy.md.twig');
        $markdown = $template->render($policy->export());

        $formatted_output = Renderer::createFromMarkdown($markdown);
        $output->writeln((string) $formatted_output);
        return 0;
    }
}
