<?php

namespace Drutiny\AuditResponse;

class NoAuditResponseFoundException extends \Exception {
  protected $name;
  public function __construct(string $name, string $message)
  {
    $this->name = $name;
    parent::__construct($message);
  }

  public function getPolicyName()
  {
    return $this->name;
  }

}
 ?>
