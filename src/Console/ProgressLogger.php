<?php

namespace Drutiny\Console;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;


class ProgressLogger implements LoggerInterface {
  use LoggerTrait;

  protected $output;
  protected $indicator;
  protected $topic;
  protected $formatLevelMap = [
      LogLevel::EMERGENCY => 'error',
      LogLevel::ALERT => 'error',
      LogLevel::CRITICAL => 'error',
      LogLevel::ERROR => 'error',
      LogLevel::WARNING => 'info',
      LogLevel::NOTICE => 'info',
      LogLevel::INFO => 'info',
      LogLevel::DEBUG => 'info',
  ];

  public function __construct(ConsoleOutputInterface $output)
  {
    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
      $progress_output = $output;
      $this->output = new ConsoleLogger(new NullOutput());
    }
    else {
      $this->output = new ConsoleLogger($output);
      $progress_output = new NullOutput();
    }
    $this->indicator = new ProgressIndicator($progress_output, 'very_verbose');
    $this->indicator->start('Starting');
  }

  public function setMessage($message)
  {
    $this->indicator->setMessage($message);
  }

  public function setTopic($topic)
  {
    $this->topic = sprintf('<info>[[%s]]</info>', $topic);
    $this->indicator->setMessage($topic);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array())
  {
    $this->indicator->advance();
    $this->indicator->setMessage(strtr('topic<f>[level]</f>message', [
      'topic' => $this->topic,
      'f' => $this->formatLevelMap[$level],
      'level' => mb_strtoupper($level),
      'message' => str_replace(PHP_EOL, "|", $message)
    ]));

    $this->output->log($level, $message, $context);
  }

  public function __destruct()
  {
    $this->indicator->finish('Finished');
  }
}

 ?>
