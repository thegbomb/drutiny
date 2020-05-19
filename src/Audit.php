<?php

namespace Drutiny;

use Drutiny\Sandbox\Sandbox;
use Drutiny\AuditValidationException;
use Drutiny\Audit\AuditInterface;

/**
 *
 */
abstract class Audit implements AuditInterface
{
    public function configure() {}

  /**
   * @param Sandbox $sandbox
   * @return
   */
    abstract public function audit(Sandbox $sandbox);

  /**
   * @param Sandbox $sandbox
   * @return
   * @throws \Drutiny\AuditValidationException
   */
    final public function execute(Sandbox $sandbox)
    {
        $this->validate($sandbox);
        return $this->audit($sandbox);
    }

    final protected function validate(Sandbox $sandbox)
    {
        $reflection = new \ReflectionClass($this);

      // Call any functions that begin with "require" considered
      // prerequisite classes.
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
        $validators = array_filter($methods, function ($method) {
            return strpos($method->name, 'require') === 0;
        });

        try {
            foreach ($validators as $method) {
                if (call_user_func([$this, $method->name], $sandbox) === false) {
                    throw new AuditValidationException("Validation failed: {$method->name}");
                }
            }
        } catch (\Exception $e) {
            throw new AuditValidationException("Audit failed validation at " . $method->getDeclaringClass()->getFilename() . " [$method->name]: " . $e->getMessage());
        }
    }
}
