<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class PolicyListCommand extends DrutinyBaseCommand
{
    use LanguageCommandTrait;
  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('policy:list')
        ->setDescription('Show all policies available.')
        ->addOption(
            'filter',
            't',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Filter list by tag'
        )
        ->addOption(
            'source',
            's',
            InputOption::VALUE_OPTIONAL,
            'Filter by source'
        );
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progress = $this->getProgressBar();
        $progress->start(4);

        $this->initLanguage($input);

        $progress->setMessage("Loading policy library from policy sources.");
        $list = $this->getPolicyFactory()->getPolicyList();

        if ($source_filter = $input->getOption('source')) {
            $progress->setMessage("Filtering policies by source: $source_filter");
            $list = array_filter($list, function ($policy) use ($source_filter) {
                return $source_filter == $policy['source'];
            });
        }
        $progress->advance();

        $progress->setMessage("Mapping policy utilisation by profile.");
        $profiles = array_map(function ($profile) {
          return $this->getProfileFactory()->loadProfileByName($profile['name']);
        }, $this->getProfileFactory()->getProfileList());

        $progress->advance();
        $rows = array();
        foreach ($list as $listedPolicy) {
            $row = array(
            'description' => '<options=bold>' . wordwrap($listedPolicy['title'], 50) . '</>',
            'name' => $listedPolicy['name'],
            'source' => $listedPolicy['source'],
            'profile_util' => count(array_filter($profiles, function ($profile) use ($listedPolicy) {
                return in_array($listedPolicy['name'], array_keys($profile->policies));
              })),
            );
            $rows[] = $row;
        }

        usort($rows, function ($a, $b) {
            $x = [strtolower($a['name']), strtolower($b['name'])];
            sort($x, SORT_STRING);

            return $x[0] == strtolower($a['name']) ? -1 : 1;
        });
        $progress->finish();

        $io = new SymfonyStyle($input, $output);
        $headers = ['Title', 'Name', 'Source', 'Profile Utilization'];
        $io->table($headers, $rows);

        return 0;
    }

  /**
   *
   */
    protected function formatDescription($text)
    {
        $lines = explode(PHP_EOL, $text);
        $text = implode(' ', $lines);
        return wordwrap($text, 50);
    }
}
