<?php

namespace Drutiny\Console;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;


class ProgressLogger implements LoggerInterface {
  use LoggerTrait;

  protected $logger;
  protected $output;
  protected $buffer;
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

  public function __construct(OutputInterface $output)
  {
    $this->output = $output;
    $this->buffer = new BufferedOutput;

    if ($output->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL) {
      $progress_output = $this->buffer;
      $this->logger = new ConsoleLogger(new NullOutput());
    }
    else {
      $this->logger = new ConsoleLogger($this->buffer);
      $progress_output = new NullOutput();
    }
    $this->indicator = new ProgressIndicator($progress_output, 'very_verbose');
    $this->indicator->start('Starting');
  }

  /**
   * Call when its safe to begin output to stdout.
   */
  public function flushBuffer()
  {
    if ($this->output->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL) {
      $this->indicator = new ProgressIndicator($this->output, 'very_verbose');
      $this->indicator->start('Starting');
    }
    $this->output->write($this->buffer->fetch());
    $this->logger = new ConsoleLogger($this->output);
    return $this;
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

    $this->logger->log($level, $message, $context);
  }

  public function __destruct()
  {
    $this->indicator->finish('Finished');
  }
}

 ?>
