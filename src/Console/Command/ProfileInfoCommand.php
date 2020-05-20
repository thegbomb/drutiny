<?php

namespace Drutiny\Console\Command;

use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\ProfileFactory;
use Twig\Environment;

/**
 *
 */
class ProfileInfoCommand extends Command
{

  protected $profileFactory;
  protected $twig;

  public function __construct(ProfileFactory $factory, Environment $twig)
  {
      $this->profileFactory = $factory;
      $this->twig = $twig;
      parent::__construct();
  }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('profile:info')
        ->setDescription('Display information about a profile.')
        ->addArgument(
            'profile',
            InputArgument::REQUIRED,
            'The name of the profile to display.'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $render = new SymfonyStyle($input, $output);

        $profile = $this->profileFactory->loadProfileByName($input->getArgument('profile'));

        $template = $this->twig->load('docs/profile.md.twig');
        $markdown = $template->render($profile->export());

        $formatted_output = Renderer::createFromMarkdown($markdown);
        $output->writeln((string) $formatted_output);
        return 0;
    }
}
