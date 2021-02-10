<?php

namespace Drutiny\Console\Helper;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Terminal;


/**
 * Pipes log into progress bar.
 */
Class MonologProgressBarHandler extends AbstractProcessingHandler {

  protected ProgressBar $progressBar;
  protected Terminal $terminal;

  public function __construct(ProgressBar $progressBar, Terminal $terminal, $level = Logger::DEBUG, bool $bubble = true)
  {
      parent::__construct($level, $bubble);
      $this->progressBar = $progressBar;
      $this->terminal = $terminal;
  }

  /**
   * {@inheritDoc}
   */
  protected function getDefaultFormatter(): FormatterInterface
  {
      return new LineFormatter('%message%');
  }

  /**
   * {@inheritdoc}
   */
  protected function write(array $record): void
  {
      $message = substr($record['formatted'], 0, min($this->terminal->getWidth(), strlen($record['formatted'])));
      $this->progressBar->setMessage($message);
  }
}


 ?>
