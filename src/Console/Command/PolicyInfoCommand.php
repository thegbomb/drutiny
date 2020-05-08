<?php

namespace Drutiny\Console\Command;

use Drutiny\PolicyFactory;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

/**
 *
 */
class PolicyInfoCommand extends Command
{
  protected $policyFactory;
  protected $twig;


  public function __construct(PolicyFactory $factory, Environment $twig)
  {
      $this->policyFactory = $factory;
      $this->twig = $twig;
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
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $policy = $this->policyFactory->loadPolicyByName($input->getArgument('policy'));

        $template = $this->twig->load('docs/policy.md.twig');
        $markdown = $template->render($policy->export());

        $formatted_output = Renderer::createFromMarkdown($markdown);
        $output->writeln((string) $formatted_output);
        return 0;
    }
}
