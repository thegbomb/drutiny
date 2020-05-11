<?php

namespace Drutiny\Target\Bridge\Drush;

use Drutiny\Target\Bridge\ExecutionInterface;
use Drutiny\Target\Bridge\ExecutionBridgeTrait;

class DrushBridge implements DrushBridgeInterface {
  use ExecutionBridgeTrait;

  protected const LAUNCHERS = ['drush-launcher', 'drush.launcher', 'drush'];
  protected $supportedCommandMap = [
    'pmList' => 'pm:list',
    'status' => 'status'
  ];
  public function __construct(ExecutionInterface $bridge)
  {
    $this->execBridge = $bridge;
  }

  public function __call($cmd, $args)
  {
    if (!isset($this->supportedCommandMap[$cmd])) {
      throw new \RuntimeException("Drush command not supported: $cmd.");
    }
    // If the last argument is an array, it is an array of options.
    $options = is_array(end($args)) ? array_pop($args) : [];

    // Ensure the root argument is set.
    if (!isset($options['root']) && !isset($options['r'])) {
      $options['root'] = '$DRUSH_ROOT';
    }

    // Setup the options to pass into the command.
    foreach ($options as $key => $value) {
      $is_short = strlen($key) == 1;
      $opt = $is_short ? '-'.$key : '--'.$key;
      // Consider as flag. e.g. --debug.
      if (is_bool($value) && $value) {
        $args[] = $opt;
        continue;
      }
      $delimiter = $is_short ? ' ' : "=";
      // Key/value option. E.g. --format=json
      $args[] = $opt.$delimiter.sprintf('%s', $value);
    }

    // Prepend the drush launcher to use.
    $launcher = '$(which ' . implode(' || which ', static::LAUNCHERS) . ')';
    array_unshift($args, $launcher, $this->supportedCommandMap[$cmd]);

    $command = implode(' ', $args);

    // Return an object ready to run the command. This allows the caller
    // of this command to be able to specify the preprocess function easily.
    return new class($command, $this->execBridge) {
      protected $cmd;
      protected $bridge;
      public function __construct($cmd, ExecutionInterface $bridge)
      {
        $this->cmd = $cmd;
        $this->bridge = $bridge;
      }
      public function run(callable $outputProcessor = NULL)
      {
        return $this->bridge->run($this->cmd, $outputProcessor);
      }
    };
  }
}
