<?php

namespace Drutiny;

use Drutiny\Audit\AuditInterface;
use Drutiny\AuditValidationException;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\TargetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 *
 */
abstract class Audit implements AuditInterface
{
    protected $definition;
    protected $logger;
    protected $container;
    protected $target;
    protected $expressionLanguage;

    public function __construct(
      ContainerInterface $container,
      TargetInterface $target,
      LoggerInterface $logger,
      ExpressionLanguage $expressionLanguage
      )
    {
      $this->container = $container;
      $this->target = $target;
      $this->logger = $logger;
      $this->definition = new InputDefinition();
      $this->expressionLanguage = $expressionLanguage;
      $this->configure();
    }

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

    /**
     * Set information about a parameter.
     */
    protected function addParameter(string $name, int $mode = null, string $description = '', $default = null)
    {
        if (!isset($this->definition)) {
          $this->definition = new InputDefinition();
        }
        $this->definition->addArgument(new InputArgument($name, $mode, $description, $default));
        return $this;
    }
}
