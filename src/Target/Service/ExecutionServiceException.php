<?php

namespace Drutiny\Target\Service;

class ExecutionServiceException extends \Exception {
  protected array $serviceHandlers = [];

  public function __construct(array $exceptions, string $method, array $args = [])
  {
    $message[] = sprintf('All execution services attempting method "%s" failed:', $method);
    foreach ($exceptions as $index => $e) {
      $message[] = sprintf("- %s: %s", $index, $e->getMessage());
    }
    if ($method == 'run') {
      $message[] = 'command: '.$args[0];
    }
    parent::__construct(implode("\n", $message));
  }
}
