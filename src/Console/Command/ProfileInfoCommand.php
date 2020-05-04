<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Drutiny\Profile\ProfileSource;
use Drutiny\Profile;

/**
 *
 */
class ProfileInfoCommand extends Command
{

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

        $profile = ProfileSource::loadProfileByName($input->getArgument('profile'));

        $render->title($profile->getTitle());
        $render->text($profile->getDescription());
        $render->table([], [
        ['Name', $profile->getName()],
        ['Filepath', $profile->getFilepath()],
        ]);

        if ($policies = $profile->getAllPolicyDefinitions()) {
            $render->text('Policies included:');
            $render->listing(array_keys($policies));
        }

        if ($listing = $profile->getIncludes()) {
            $render->text('Inherits additional policies from:');
            $render->listing(array_keys($listing));
        }
    }
}
