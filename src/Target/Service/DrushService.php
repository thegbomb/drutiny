<?php

namespace Drutiny\Target\Service;

use Drutiny\Target\Service\ExecutionInterface;
use Drutiny\Target\Service\RemoteService;

class DrushService {

  protected const LAUNCHERS = ['drush-launcher', 'drush.launcher', 'drush'];
  protected $supportedCommandMap = [
    'configGet' => 'config:get',
    'pmList' => 'pm:list',
    'pmSecurity' => 'pm:security',
    'stateGet' => 'state:get',
    'status' => 'status',
    'userInformation' => 'user:information',
    'sqlq' => 'sqlq',
  ];
  public function __construct(ExecutionInterface $service)
  {
    $this->execService = $service;
  }

  public function isRemote()
  {
      return $this->execService instanceof RemoteService;
  }

  /**
   * Usage: ->configGet('system.settings', ['format' => 'json'])
   * Executes: drush config:get 'system.settings' --format=json
   */
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

    // Quote all arguments.
    array_walk($args, function (&$arg) {
        $arg = escapeshellarg($arg);
    });

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
      // Key/value option. E.g. --format='json'
      $args[] = $opt.$delimiter.escapeshellarg($value);
    }

    // Prepend the drush launcher to use.
    $launcher = '$(which ' . implode(' || which ', static::LAUNCHERS) . ')';
    array_unshift($args, $launcher, $this->supportedCommandMap[$cmd]);

    $command = implode(' ', $args);

    // Return an object ready to run the command. This allows the caller
    // of this command to be able to specify the preprocess function easily.
    return new class($command, $this->execService) {
      protected $cmd;
      protected $service;
      public function __construct($cmd, ExecutionInterface $service)
      {
        $this->cmd = $cmd;
        $this->service = $service;
      }
      public function run(callable $outputProcessor = NULL)
      {
        return $this->service->run($this->cmd, $outputProcessor);
      }
    };
  }
}
