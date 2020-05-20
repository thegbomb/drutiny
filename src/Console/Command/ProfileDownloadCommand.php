<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Profile\ProfileSource;
use Drutiny\ProfileFactory;
use Drutiny\Profile;

/**
 *
 */
class ProfileDownloadCommand extends Command
{
    protected $profileFactory;

    public function __construct(ProfileFactory $factory)
    {
        $this->profileFactory = $factory;
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('profile:download')
        ->setDescription('Download a remote profile locally.')
        ->addArgument(
            'profile',
            InputArgument::REQUIRED,
            'The name of the profile to download.'
        )
        ->addArgument(
            'source',
            InputArgument::OPTIONAL,
            'The source to download the profile from.'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $render = new SymfonyStyle($input, $output);

        $profile = $this->profileFactory->loadProfileByName($input->getArgument('profile'));
        $export = $profile->export();
        foreach ($export['policies'] as &$override) {
          unset($override['name'], $override['weight']);
        }
        $filename = "{$profile->name}.profile.yml";
        $export['uuid'] = $filename;
        $output = Yaml::dump($export, 6);
        file_put_contents($filename, $output);
        $render->success("$filename written.");
        return 0;
    }
}
