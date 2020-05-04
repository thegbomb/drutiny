<?php

namespace Drutiny\Sandbox;

use Drutiny\Audit;
use Drutiny\AuditInterface;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Driver\Exec;
use Drutiny\Policy;
use Drutiny\RemediableInterface;
use Drutiny\Target\TargetInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Run check in an isolated environment.
 */
class Sandbox
{
    use ParameterTrait;
    use ReportingPeriodTrait;

    protected $target;
    protected $audit;
    protected $policy;
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

  /**
   * Create a new Sandbox.
   *
   * @param string $target
   *   The class name of the target to create.
   * @param Policy $policy
   *
   * @throws \Exception
   */
    public function create(TargetInterface $target, Policy $policy)
    {
      // Default reporting period is last 24 hours to the nearest hour.
        $start = new \DateTime(date('Y-m-d H:i:s', strtotime('-24 hours')));
        $end   = clone $start;
        $end->add(new \DateInterval('PT24H'));

        $audit = $this->container->get($policy->getProperty('class'));
        $sandbox = new static($this->container);

        return $sandbox->setTarget($target)
        ->setPolicy($policy)
        ->setAudit($audit)
        ->setReportingPeriod($start, $end);
    }

    public function setTarget(TargetInterface $target)
    {
        $this->target = $target;
        return $this;
    }

    public function setPolicy(Policy $policy)
    {
        $this->policy = $policy;
        return $this;
    }

    public function setAudit(AuditInterface $audit)
    {
        $this->audit = $audit;
        return $this;
    }

  /**
   * Run the check and capture the outcomes.
   */
    public function run()
    {
        $response = new AuditResponse($this->getPolicy());
        $watchdog = $this->container->get('logger');

        $watchdog->info('Auditing ' . $this->policy->getProperty('name'));
        try {
          // Ensure policy dependencies are met.
            foreach ($this->policy->getDepends() as $dependency) {
                // Throws DependencyException if dependency is not met.
                $dependency->execute($this);
            }

          // Run the audit over the policy.
            $outcome = $this->audit->execute($this);

          // Log the parameters output.
            $watchdog->debug("Tokens:\n" . Yaml::dump($this->getParameterTokens(), 4));

          // Set the response.
            $response->set($outcome, $this->getParameterTokens());
        } catch (\Drutiny\Policy\DependencyException $e) {
            $this->setParameter('exception', $e->getMessage());
            $response->set($e->getDependency()->getFailBehaviour(), $this->getParameterTokens());
        } catch (\Drutiny\AuditValidationException $e) {
            $this->setParameter('exception', $e->getMessage());
            $watchdog->warning($e->getMessage());
            $response->set(Audit::NOT_APPLICABLE, $this->getParameterTokens());
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($this->container->get('verbosity')->get() > OutputInterface::VERBOSITY_NORMAL) {
                $message .= PHP_EOL . $e->getTraceAsString();
            }
            $this->setParameter('exception', $message);
            $response->set(Audit::ERROR, $this->getParameterTokens());
        }

        return $response;
    }

  /**
   * Remediate the check if available.
   */
    public function remediate()
    {
        $response = new AuditResponse($this->getPolicy());
        try {
          // Do not attempt remediation on checks that don't support it.
            if (!($this->getAuditor() instanceof RemediableInterface)) {
                throw new \Exception(get_class($this->getAuditor()) . ' is not remediable.');
            }

          // Make sure remediation does report false positives due to caching.
            Cache::purge();
            $outcome = $this->getAuditor()->remediate($this);
            $response->set($outcome, $this->getParameterTokens());
        } catch (\Exception $e) {
            $this->setParameter('exception', $e->getMessage());
            $response->set(Audit::ERROR, $this->getParameterTokens());
        }
        return $response;
    }

  /**
   *
   */
    public function getAuditor()
    {
        return $this->audit;
    }

  /**
   *
   */
    public function getPolicy()
    {
        return $this->policy;
    }

  /**
   *
   */
    public function getTarget()
    {
        return $this->target;
    }

  /**
   * Pull the logger from the Container.
   */
    public function logger()
    {
        return $this->container->get('logger');
    }
}
