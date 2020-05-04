<?php

namespace Drutiny\Console\Command;

use Drutiny\Profile;
use Drutiny\Profile\PolicyDefinition;
use Drutiny\PolicySource\PolicySource;
use Drutiny\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class ProfileGenerateCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('profile:generate')
      ->setDescription('Create a profile')
      ->addOption(
        'title',
        't',
        InputOption::VALUE_OPTIONAL,
        'The title to use for the profile. Will be prompted if omitted.'
      )
      ->addOption(
        'filepath',
        'f',
        InputOption::VALUE_OPTIONAL,
        'The filepath to write the profile to. Will be prompted if omitted.'
      )
      ->addOption(
        'policy',
        'p',
        InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
        'Specify policies to include. Will be prompted in addition.',
        []
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $profile = new Profile();

    // Title.
    $title = $input->getOption('title');
    $profile->setTitle(empty($title) ? $io->ask('Profile title') : $title);

    // Description.
    $profile->setDescription($io->ask('Provide a brief description of what the profile is for. Markdown is supported'));

    // Policies.
    $policies = array_keys(PolicySource::getPolicyList());
    $weight = 0;

    foreach ($input->getOption('policy') as $name) {
      if (in_array($name, $policies)) {
        $profile->addPolicyDefinition(
          PolicyDefinition::createFromProfile($name, $weight++, []));
      }
      else {
        throw new \Exception("Policy '$name' does not exist.");
      }
    }

    $question = new Question("Add policies you would like added to this profile", FALSE);

    do {
      $question->setAutocompleterValues($policies);

      if (!$policy = $io->askQuestion($question)) {
        break;
      }

      $profile->addPolicyDefinition(
        PolicyDefinition::createFromProfile($policy, $weight++, []));

      $question = new Question("Add another policy or <enter> to finish", FALSE);
    }
    while (TRUE);


    $filename = realpath('.') . '/' . $this->machineName($profile->getTitle()) . '.profile.yml';
    $filepath = $input->getOption('filepath');
    $profile->setFilepath(empty($filepath) ? $io->ask("File location", $filename) : $filepath);

    file_put_contents($profile->getFilepath(), Yaml::dump($profile->dump()));
    $io->success("Profile written to: " . $profile->getFilepath());
  }

  protected function machineName($value)
  {
    return preg_replace('/[^0-9a-zA-Z]/', '', $value);
  }

}
