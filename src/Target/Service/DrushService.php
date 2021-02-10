<?php

namespace Drutiny\Target\Service;

use Drutiny\Target\Service\ExecutionInterface;
use Drutiny\Target\Service\RemoteService;

class DrushService {

  protected const LAUNCHERS = ['../vendor/drush/drush/drush', 'drush-launcher', 'drush.launcher', 'drush'];
  protected $supportedCommandMap = [
    'configGet' => 'config:get',
    'pmList' => 'pm:list',
    'pmSecurity' => 'pm:security',
    'stateGet' => 'state:get',
    'status' => 'status',
    'userInformation' => 'user:information',
    'sqlq' => 'sqlq',
    'updb' => 'updb',
    'updatedbStatus' => 'updatedb:status',
  ];
  public function __construct(ExecutionInterface $service)
  {
    $this->execService = $service;
  }

  public function isRemote()
  {
      return $this->execService instanceof RemoteService;
  }

  public function runtime(\Closure $func, ...$args)
  {
      $reflection = new \ReflectionFunction($func);
      $filename = $reflection->getFileName();

      // it's actually - 1, otherwise you wont get the function() block
      $start_line = $reflection->getStartLine();
      $end_line = $reflection->getEndLine();
      $length = $end_line - $start_line;
      $source = file($filename);
      $body = array_slice($source, $start_line, $length);
      $body[0] = substr($body[0], strpos($body[0], 'function'));
      array_pop($body);

      $body = array_map('trim', $body);

      // // Compress code.
      $code = implode('', array_filter($body, function ($line) {
        // Ignore empty lines.
        if (empty($line)) {
          return false;
        }
        // Ignore comments. /* style will still be allowed.
        if (strpos($line, '//') === false) {
          return true;
        }
        return false;
      }));

      // Build code to pass in parameters
      $initCode = '';
      foreach ($reflection->getParameters() as $idx => $param) {
        $initCode .= strtr('$var = value;', [
          'var' => $param->name,
          'value' => var_export($args[$idx], true)
        ]);
      }
      // Compress.
      $initCode = str_replace(PHP_EOL, '', $initCode);
      $wrapper = strtr('$f=function(){@code}; echo json_encode($f());', [
        '@code' => $initCode.$code
      ]);
      $wrapper = base64_encode($wrapper);
      $command = strtr('echo @code | base64 --decode | @launcher -r $DRUSH_ROOT php-script -', [
        '@code' => $wrapper,
        '@launcher' => $launcher = '$(which ' . implode(' || which ', static::LAUNCHERS) . ')',
      ]);
      return $this->execService->run($command, function ($output) {
        return json_decode($output, true);
      });
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
    return new _helper($command, $this->execService);
  }
}

class _helper {
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
}
