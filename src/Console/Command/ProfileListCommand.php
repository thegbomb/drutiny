<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 *
 */
class ProfileListCommand extends DrutinyBaseCommand
{

  use LanguageCommandTrait;

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('profile:list')
        ->setDescription('Show all profiles available.');
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initLanguage($input);
        $render = new SymfonyStyle($input, $output);
        $progress = $this->getProgressBar();
        $progress->start(1);
        $progress->setMessage("Pulling profiles from profile sources...");
        $profiles = $this->getProfileFactory()->getProfileList();

      // Build array of table rows.
        $rows = array_map(function ($profile) {
            return [$profile['title'], $profile['name'], $profile['source']];
        }, $profiles);

      // Sort rows by profile name alphabetically.
        usort($rows, function ($a, $b) {
            if ($a[1] === $b[1]) {
                return 0;
            }
            $sort = [$a[1], $b[1]];
            sort($sort);
            return $a[1] === $sort[0] ? -1 : 1;
        });
        $progress->finish();
        $progress->clear();
        $render->table(['Profile', 'Name', 'Source'], $rows);
        $render->note("Use drutiny profile:info to view more information about a profile.");
        return 0;
    }
}
