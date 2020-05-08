<?php

namespace Drutiny;

use Symfony\Component\Console\Helper\ProgressBar as SymfonyProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drutiny\Container;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * LoggerInterface wrapper around the Symfony ProgressBar.
 */
class ProgressBar extends AbstractLogger
{
    use ContainerAwareTrait;

    protected $status = true;
    protected $bar;
    protected $topic;
    protected $output;
    protected $logger;
  /* @var int */
    protected $steps;

    public function __construct(ContainerInterface $container, OutputInterface $output, ConsoleLogger $logger)
    {
        $this->setContainer($container);
        $this->output = $output;
        $this->logger = $logger;
        $this->steps  = 0;
    }

    public function start()
    {
        if ($this->status) {
            $this->bar()->start();
        }
        return $this;
    }

    protected function bar()
    {
        if (empty($this->bar)) {
            $progress = new SymfonyProgressBar($this->output, $this->steps);
            $progress->setFormatDefinition('custom', " <comment>%message%</comment>\n %current%/%max% <info>[%bar%]</info> %percent:3s%% %memory:6s%");
            $progress->setFormat('custom');
            $progress->setMessage("Starting...");
            $progress->setBarWidth(80);
            $this->bar = $progress;

            if ($this->status) {
                $this->container->set('logger', $this);
            }
        }

        return $this->bar;
    }

    public function setMaxSteps(int $maxSteps)
    {
      $this->bar()->setMaxSteps($maxSteps);
      $this->steps = $maxSteps;
      return $this;
    }

    public function resetSteps($steps = 1)
    {
        return $this->setMaxSteps($steps);
    }

  /**
   * Prevent the ProgressBar from continuing to function.
   */
    public function disable()
    {
        if ($this->status) {
            $this->status = false;
            !empty($this->bar) && $this->bar()->finish();
            $this->container->set('logger', new ConsoleLogger($this->output));
        }
        return $this;
    }

  /**
   * Set the topic for which each subsequent log message is in context too.
   */
    public function setTopic($topic)
    {
        $this->topic = $topic;
        return $this;
    }

  /**
   * {@inheritdoc}
   */
    public function log($level, $message, array $context = array())
    {
        $lines = explode(PHP_EOL, $message);
        $message = array_shift($lines);
        if (count($lines) > 1) {
            $message .= '... (showing 1 line of ' . count($lines) . ')';
        }
        if ($this->status && ($level != LogLevel::DEBUG)) {
            $level = strtoupper($level);
            $message = sprintf("[%s][%s] %s", $this->topic, $level, $message);
            $max_length = (getenv('COLUMNS') ?: 80) - 5;

            if (strlen($message) > $max_length) {
                $message = substr($message, 0, $max_length) . '...';
            }

            $this->bar()->setMessage($message);
            $this->bar()->display();
        }
        return $this;
    }

  /**
   * Advance() wrapper.
   */
    public function advance($step = 1)
    {
        if ($this->status) {
            $this->bar()->advance($step);
        }
        return $this;
    }

  /**
   * Close up the progress bar.
   */
    public function finish()
    {
        if ($this->status) {
            $this->bar()->setMessage("Done");
            $this->disable();
            echo "\n";
        }
        return $this;
    }

    public function getSteps():int
    {
      return $this->steps;
    }
}
