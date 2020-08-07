<?php

namespace Drutiny\Console;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Output\OutputInterface;

class LogFileLogger extends AbstractLogger {
    protected $fs;
    protected $logDir;
    protected $progressBar;
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

    public function __construct(string $logDir, Filesystem $fs, ProgressBar $progressBar, Verbosity $verbosity)
    {
        $this->fs = $fs;
        $this->logDir = $logDir;
        $this->progressBar = $progressBar;
        $this->terminal = new Terminal();
        $this->verbosity = $verbosity;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        // Only log above the set verbosity.
        $verbosity = $this->verbosityLevelMap[$level];
        if ($verbosity > $this->verbosity->get()) {
          return;
        }
        $trace = '';

        if ($this->verbosity->get() === OutputInterface::VERBOSITY_DEBUG) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $trace = strtr('file:line', end($backtrace));
        }

        $datetime = new \DateTime();
        $log = strtr("datetime|pid|level|trace message\n", [
          'level' => strtoupper($level),
          'trace' => $trace,
          'pid' => getmypid(),
          'message' => $this->interpolate($message, $context),
          'datetime' => $datetime->format("Y-m-d\TH:i:s.vP"),
        ]);
        $this->fs->appendToFile($this->logDir . '/drutiny.log', $log);

        $message = $this->interpolate($message, $context);
        $message = str_replace(PHP_EOL, " ", $message);
        if (strlen($message) > $this->terminal->getWidth()) {
            $message = substr($message, 0, $this->terminal->getWidth() - 100);
        }
        // Update the progress bar if present.
        $this->progressBar->setMessage($message);
    }

    protected function interpolate(string $message, array $context = array())
    {
        foreach ($context as $key => $value) {
          $message = str_replace('{'.$key.'}', $value, $message);
        }
        return $message;
    }
}

 ?>
