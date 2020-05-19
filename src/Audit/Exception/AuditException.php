<?php

namespace Drutiny\Audit\Exception;

use Drutiny\Audit\AuditInterface;

class AuditException extends \Exception {

  public function getStatus()
  {
    return AuditInterface::FAIL;
  }
}
