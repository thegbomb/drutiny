<?php

namespace Drutiny;

use Async\ForkManager;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\AuditResponse\NoAuditResponseFoundException;
use Drutiny\Entity\ExportableInterface;
use Drutiny\Entity\SerializableExportableTrait;
use Drutiny\Sandbox\ReportingPeriodTrait;
use Drutiny\Target\TargetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Assessment implements ExportableInterface, AssessmentInterface
{
    use ReportingPeriodTrait;
    use SerializableExportableTrait {
      import as importUnserialized;
    }

    /**
     * @var string URI
     */
    protected $uri = '';
    protected $results = [];
    protected $successful = true;
    protected $severityCode = 1;
    protected $logger;
    protected $container;
    protected $remediable = [];
    protected $forkManager;
    protected $statsByResult = [];
    protected $statsBySeverity = [];
    protected $policyOrder = [];

    public function __construct(LoggerInterface $logger, ContainerInterface $container, ForkManager $forkManager)
    {
        $this->logger = $logger;
        $this->container = $container;
        $this->forkManager = $forkManager;
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

        $promises = [];
        foreach ($policies as $policy) {
            $this->policyOrder[] = $policy->name;
            $this->logger->info("Assessing '{policy}' against {uri}", [
              'policy' => $policy->name,
              'uri' => $this->uri,
            ]);

            $audit = $this->container->get($policy->class);
            $audit->setParameter('reporting_period_start', $start)
                  ->setParameter('reporting_period_end', $end);

            if ($target !== $audit->getTarget()) {
              throw new \Exception("Audit target not the same as assessment target.");
            }
            $audit->getTarget()->setUri($this->uri);

            $this->forkManager->run(function () use ($audit, $policy, $remediate) {
              return $audit->execute($policy, $remediate);
            });
        }
        $returned = 0;
        foreach ($this->forkManager->receive() as $response) {
            $returned++;
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
                $this->logger->info("\xE2\x9A\xA0 Remediating " . $response->getPolicy()->title);
                $this->setPolicyResult($sandbox->remediate());
            }

            $this->logger->info(sprintf('Policy "%s" assessment on %s completed: %s.', $response->getPolicy()->title, $this->uri(), $response->getType()));
        }

        $total = count($policies);
        $this->logger->debug("Assessment returned $returned/$total from the fork manager.");

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
        return array_map(function ($name) {
            return $this->results[$name];
        }, $this->policyOrder);
        //return $this->results;
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

    /**
     * {@inheritdoc}
     */
    public function export()
    {
      return [
        'statsBySeverity' => $this->statsBySeverity,
        'statsBySeverity' => $this->statsBySeverity,
        'uri' => $this->uri,
        'results' => $this->results,
        'remediable' => $this->remediable,
      ];
    }

    public function import(array $export)
    {
      $this->importUnserialized($export);
      $this->container = drutiny();
      $this->logger = drutiny()->get('logger');
      $this->async = drutiny()->get('async');
    }
}
