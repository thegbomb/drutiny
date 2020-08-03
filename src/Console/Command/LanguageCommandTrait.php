<?php

namespace Drutiny\Console\Command;

use Drutiny\LanguageManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

/**
 *
 */
trait LanguageCommandTrait
{
  /**
   * @inheritdoc
   */
    protected function configureLanguage()
    {
        $this
        ->addOption(
            'language',
            '',
            InputOption::VALUE_OPTIONAL,
            'Define which language to use for policies and profiles. Defaults to English (en).',
            'en'
        );
    }

    protected function initLanguage(InputInterface $input)
    {
      // Set global language used by policy/profile sources.
      $this->getLanguageManager()->setLanguage($input->getOption('language'));
    }

    /**
     * Get container language manager.
     */
    protected function getLanguageManager():LanguageManager
    {
        return $this->getContainer()->get('language_manager');
    }
}
