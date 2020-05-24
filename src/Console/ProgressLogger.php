<?php

namespace Drutiny\Console;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;


class ProgressLogger implements LoggerInterface {
  use LoggerTrait;

  const MAX_INDICATOR_MSG_LENGTH = 96;

  protected $output;
  protected $buffer;
  protected $flushed = false;
  protected $tail;
  protected $section;
  protected $lastLogLevel;
  protected $verbosityLevelMap = [
      LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
      LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
      LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
  ];

  public function __construct(OutputInterface $output)
  {
    $this->output = $output;
    $this->buffer = new BufferedOutput();
    $this->tail = $this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;
    $this->section = ($output instanceof ConsoleOutput) ? $output->section() : $output;

    $outputStyle = new OutputFormatterStyle('red', 'yellow', ['bold', 'blink']);
    $output->getFormatter()->setStyle('emergency', $outputStyle);

    $outputStyle = new OutputFormatterStyle('white', 'red', ['bold', 'blink']);
    $output->getFormatter()->setStyle('alert', $outputStyle);

    $outputStyle = new OutputFormatterStyle('white', 'red');
    $output->getFormatter()->setStyle('critical', $outputStyle);

    $outputStyle = new OutputFormatterStyle('red');
    $output->getFormatter()->setStyle('error', $outputStyle);

    $outputStyle = new OutputFormatterStyle('yellow');
    $output->getFormatter()->setStyle('warning', $outputStyle);

    $outputStyle = new OutputFormatterStyle('green');
    $output->getFormatter()->setStyle('notice', $outputStyle);

    $outputStyle = new OutputFormatterStyle('cyan');
    $output->getFormatter()->setStyle('info', $outputStyle);

    $outputStyle = new OutputFormatterStyle('default');
    $output->getFormatter()->setStyle('debug', $outputStyle);
  }

  /**
   * Call when its safe to begin output to stdout.
   */
  public function flushBuffer()
  {
      $this->output->write($this->buffer->fetch());
      $this->flushed = true;
      return $this;
  }

  public function setMessage($message)
  {
      $this->log(LogLevel::NOTICE, $message);
  }

  public function setTopic($topic)
  {
      $this->log(LogLevel::NOTICE, $topic);
  }

  public function clear()
  {
      $this->section->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array())
  {
      if (!isset($this->verbosityLevelMap[$level])) {
          throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
      }

      $output = $this->tail ? $this->output : $this->section;
      $output = $this->flushed ? $output : $this->buffer;

      // the if condition check isn't necessary -- it's the same one that $output will do internally anyway.
      // We only do it for efficiency here as the message formatting is relatively expensive.
      if ($output->getVerbosity() < $this->verbosityLevelMap[$level]) {
          return;
      }

      $message = sprintf('<%1$s>%2$s->[%3$s] %4$s</%1$s>', $level, getmypid(), $level, $this->interpolate($message, $context));

      if (in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL])) {
          $output->write($message, $this->verbosityLevelMap[$level]);
      }
      elseif (!$this->tail && method_exists($output, 'overwrite') && ($level <= $this->lastLogLevel)) {
          $output->overwrite($message, $this->verbosityLevelMap[$level]);
      }
      else {
          $output->write($message, $this->verbosityLevelMap[$level]);
      }
      $this->lastLogLevel = $level;
  }

  /**
   * Interpolates context values into the message placeholders.
   *
   * @author PHP Framework Interoperability Group
   */
  private function interpolate(string $message, array $context): string
  {
      if (false === strpos($message, '{')) {
          return $message;
      }

      $replacements = [];
      foreach ($context as $key => $val) {
          if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
              $replacements["{{$key}}"] = $val;
          } elseif ($val instanceof \DateTimeInterface) {
              $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
          } elseif (\is_object($val)) {
              $replacements["{{$key}}"] = '[object '.\get_class($val).']';
          } else {
              $replacements["{{$key}}"] = '['.\gettype($val).']';
          }
      }

      return strtr($message, $replacements);
  }
}

 ?>
