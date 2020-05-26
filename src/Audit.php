<?php

namespace Drutiny;

use Drutiny\Audit\AuditInterface;
use Drutiny\Audit\AuditValidationException;
use Drutiny\Audit\RemediableInterface;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Entity\DataBag;
use Drutiny\Policy;
use Drutiny\Policy\DependencyException;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\NoSuchPropertyException;
use Drutiny\Target\TargetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

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
    protected $dataBag;

    final public function __construct(
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
      $this->dataBag = new DataBag([
        'parameters' => new DataBag()
      ]);
      $this->configure();
    }

    public function configure() {}

  /**
   * @return
   */
    abstract public function audit(Sandbox $sandbox);

  /**
   * @param Sandbox $sandbox
   * @return
   * @throws \Drutiny\Audit\AuditValidationException
   */
    final public function execute(Policy $policy, $remediate = false)
    {
        $response = new AuditResponse($policy);
        $this->logger->info('Auditing ' . $policy->name);
        try {
            // Ensure policy dependencies are met.
            foreach ($policy->getDepends() as $dependency) {
                // Throws DependencyException if dependency is not met.
                $dependency->execute($this);
            }

            $input = new ArrayInput($policy->getAllParameters(), $this->definition);
            $this->dataBag->get('parameters')->add($input->getArguments());

            // Run the audit over the policy.
            $outcome = $this->audit(new Sandbox($this));

            // If the audit wasn't successful and remediation is allowed, then
            // attempt to resolve the issue. TODO: Purge Cache
            if (($this instanceof RemediableInterface) && !$outcome && $remediate) {
                $outcome = $this->remediate(new Sandbox($this));
            }
        }
        catch (DependencyException $e) {
            $this->set('exception', $e->getMessage());
            $outcome = $e->getDependency()->getFailBehaviour();
        }
        catch (AuditValidationException $e) {
            $this->set('exception', $e->getMessage());
            $this->logger->warning($e->getMessage());
            $outcome = AuditInterface::NOT_APPLICABLE;
        }
        catch (NoSuchPropertyException $e)  {
            $this->set('exception', $e->getMessage());
            $this->logger->warning($e->getMessage());
            $outcome = AuditInterface::NOT_APPLICABLE;
        }
        catch (\Exception $e) {
            $message = $e->getMessage();
            if ($this->container->get('verbosity')->get() > OutputInterface::VERBOSITY_NORMAL) {
                $message .= PHP_EOL . $e->getTraceAsString();
            }
            $this->set('exception', $message);
            $this->logger->error($message);
            $outcome = AuditInterface::ERROR;
        }
        finally {
          // Log the parameters output.
          $this->logger->debug("Tokens:\n" . Yaml::dump($this->dataBag->all(), 4));

          // Set the response.
          $response->set($outcome, $this->dataBag->all());
        }

        return $response;
    }

    public function evaluate($expression)
    {
      $tokens = $this->dataBag->all();
      $tokens['target'] = $this->target;
      return $this->expressionLanguage->evaluate($expression, $tokens);
    }

    public function setParameter($name, $value)
    {
      $this->dataBag->get('parameters')->set($name, $value);
      return $this;
    }

    public function getParameter($name)
    {
      return $this->dataBag->get('parameters')->get($name);
    }

    public function set($name, $value)
    {
      $this->dataBag->set($name, $value);
      return $this;
    }

    public function get($name)
    {
      return $this->dataBag->get($name);
    }

    /**
     * Used to provide target to deprecated Sandbox object.
     * @deprecated
     */
    public function getTarget():TargetInterface
    {
      return $this->target;
    }

    /**
     * Used to provide logger to deprecated Sandbox object.
     * @deprecated
     */
    public function getLogger():LoggerInterface
    {
      return $this->logger;
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
