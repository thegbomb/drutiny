<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class ProfileShowCommand extends DrutinyBaseCommand
{
    use LanguageCommandTrait;
  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('profile:show')
        ->setDescription('Show a profile definition.')
        ->addArgument(
            'profile',
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

        $profile = $this->getProfileFactory()->loadProfileByName($input->getArgument('profile'));
        $export = $profile->export();

        if (!$input->getOption('backward-compatibility')) {
            foreach ($export['format']['html']['content'] ?? [] as &$section) {
                foreach (array_keys($section) as $attribute) {
                    $template = $this->prefixTemplate($section[$attribute]);

                    // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
                    $template = $this->preMapDrutiny2Variables($template);

                    // Convert from Mustache (supported in Drutiny 2.x) over to twig syntax.
                    $template = $this->convertMustache2TwigSyntax($template);

                    // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
                    $template = $this->mapDrutiny2toDrutiny3variables($template);
                    $section[$attribute] = $template;
                }
            }
        }

        $output->write(Yaml::dump($export, 6, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        return 0;
    }
}
