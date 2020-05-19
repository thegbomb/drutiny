<?php

namespace Drutiny;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\AuditResponse\NoAuditResponseFoundException;
use Drutiny\Target\TargetInterface;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Sandbox\ReportingPeriodTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Assessment
{
  use ReportingPeriodTrait;

  /**
   * @var string URI
   */
    protected $uri;
    protected $results = [];
    protected $successful = true;
    protected $severityCode = 1;
    protected $logger;
    protected $container;
    protected $statsByResult = [];
    protected $statsBySeverity = [];
    protected $remediable = [];

    public function __construct(LoggerInterface $logger, ContainerInterface $container)
    {
        $this->logger = $logger;
        $this->container = $container;
    }

    public function setUri($uri = 'default')
    {
        $this->uri = $uri;
        return $this;
    }

  /**
   * Assess a Target.
   *
   * @param TargetInterface $target
   * @param array $policies each item should be a Drutiny\Policy object.
   * @param DateTime $start The start date of the reporting period. Defaults to -1 day.
   * @param DateTime $end The end date of the reporting period. Defaults to now.
   * @param bool $remediate If an Drutiny\Audit supports remediation and the policy failes, remediate the policy. Defaults to FALSE.
   */
    public function assessTarget(TargetInterface $target, array $policies, \DateTime $start = null, \DateTime $end = null, $remediate = false)
    {
        $start = $start ?: new \DateTime('-1 day');
        $end   = $end ?: new \DateTime();

        // Record the reporting period in the assessment so we can pull it
        // later when rendering the report.
        $this->setReportingPeriod($start, $end);

        $policies = array_filter($policies, function ($policy) {
            return $policy instanceof Policy;
        });

        $is_progress_bar = $this->logger instanceof \Drutiny\Console\ProgressLogger;

        foreach ($policies as $policy) {
            if ($is_progress_bar) {
                $this->logger->setTopic($this->uri . '][' . $policy->title);
            }

            $this->logger->info("Assessing policy...");

          // Setup the sandbox to run the assessment.
            $sandbox = $this->container
            ->get('sandbox')
            ->create($target, $policy)
            ->setReportingPeriod($start, $end);

            $response = $sandbox->run();

            $this->statsByResult[$response->getType()] = $this->statsByResult[$response->getType()] ?? 0;
            $this->statsByResult[$response->getType()]++;

            $this->statsBySeverity[$response->getSeverity()][$response->getType()] = $this->statsBySeverity[$response->getSeverity()][$response->getType()] ?? 0;
            $this->statsBySeverity[$response->getSeverity()][$response->getType()]++;

          // Omit irrelevant AuditResponses.
            if (!$response->isIrrelevant()) {
                $this->setPolicyResult($response);
            }

          // Attempt remediation.
            if ($remediate && !$response->isSuccessful()) {
                $this->logger->info("\xE2\x9A\xA0 Remediating " . $policy->title);
                $this->setPolicyResult($sandbox->remediate());
            }

            if ($is_progress_bar) {
                $this->logger->info(sprintf('Policy "%s" assessment completed: %s.', $policy->title, $response->getType()));
            }
        }

        return $this;
    }

  /**
   * Set the result of a Policy.
   *
   * The result of a Policy is unique to an assessment result set.
   *
   * @param AuditResponse $response
   */
    public function setPolicyResult(AuditResponse $response)
    {
        $this->results[$response->getPolicy()->name] = $response;

      // Set the overall success state of the Assessment. Considered
      // a success if all policies pass.
        $this->successful = $this->successful && $response->isSuccessful();

      // If the policy failed its assessment and the severity of the Policy
      // is higher than the current severity of the assessment, then increase
      // the severity of the overall assessment.
        $severity = $response->getPolicy()->getSeverity();
        if (!$response->isSuccessful() && ($this->severityCode < $severity)) {
            $this->severityCode = $severity;
        }
        if ($response->isFailure()) {
          $this->remediable[] = $response;
        }
    }

    public function getSeverityCode():int
    {
        return $this->severityCode;
    }

  /**
   * Get the overall outcome of the assessment.
   */
    public function isSuccessful()
    {
        return $this->successful;
    }

  /**
   * Get an AuditResponse object by Policy name.
   *
   * @param string $name
   * @return AuditResponse
   */
    public function getPolicyResult(string $name)
    {
        if (!isset($this->results[$name])) {
            throw new NoAuditResponseFoundException($name, "Policy '$name' does not have an AuditResponse.");
        }
        return $this->results[$name];
    }

  /**
   * Get the results array of AuditResponse objects.
   *
   * @return array of AuditResponse objects.
   */
    public function getResults()
    {
        return $this->results;
    }

    public function getRemediableResults()
    {
      $this->remediable;
    }

  /**
   * Get the uri of Assessment object.
   *
   * @return string uri.
   */
    public function uri()
    {
        return $this->uri;
    }

    public function getStatsByResult()
    {
      return $this->statsByResult;
    }

    public function getStatsBySeverity()
    {
      return $this->statsBySeverity;
    }
}
