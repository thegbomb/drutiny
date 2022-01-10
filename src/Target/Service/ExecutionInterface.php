<?php

namespace Drutiny\Target\Service;

interface ExecutionInterface {
  /**
   * Run an exeuction command.
   *
   * @param $cmd string
   *    An array of arguments to pass as the command to run.
   * @param $preProcess callable
   *    A callback to run the output of the command through prior to caching.
   * @param $ttl int
   *    The number of seconds to cache output from.
   */
  public function run(string $cmd, callable $preProcess, int $ttl);

  /**
   * Know if an environmental variable is present.
   *
   * @param $name string
   *    The name of the environmental variable.
   * @return boolean
   *    True if the variable exists.
   */
  public function hasEnvVar(string $name):bool;
}
