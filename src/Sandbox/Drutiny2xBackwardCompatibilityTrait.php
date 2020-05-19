<?php

namespace Drutiny\Sandbox;

/**
 * Deprecated 2.x Drutiny functions on the Sandbox.
 */
trait Drutiny2xBackwardCompatibilityTrait {

  public function exec($command)
  {
    $this->logger()->warning(__METHOD__.' is a deprecated method. Please use $sandbox->getTarget()->getBridge("exec").');

    return $this->getTarget()->getBridge('exec')->run($command);
  }

  public function drush($opts = [])
  {
    $this->logger()->warning(__METHOD__.' is a deprecated method. Please use $sandbox->getTarget()->getBridge("drush").');
    //return $this->getTarget()->getBridge('drush');

    return new class ($this->getTarget(), $opts) {
      protected $target;
      protected $opts;
      public function __construct($target, $opts)
      {
        $this->target = $target;
        $this->opts = $opts;
      }
      public function __call($method, $args)
      {
        // preg_match_all('/((?:^|[A-Z])[a-z]+)/', $method, $matches);
        // $method = implode(':', array_map('strtolower', $matches[0]));
        foreach ($this->opts as $key => $value) {
          if (is_numeric($key)) {
            $args[] = '--' . $value; continue;
          }
          elseif ($value === NULL) {
            $args[] = '--' . $key; continue;
          }
          $args[] = '--' . $key . '=' . sprintf('%s', $value);
        }

        $exec = call_user_func_array([$this->target->getBridge('drush'), $method], $args);
        return $exec->run(function ($output) use ($args) {
          if (in_array("--format=json", $args)) {
            return json_decode($output, TRUE);
          }
          return explode(PHP_EOL, $output);
        });
      }
    };
  }
}

 ?>
