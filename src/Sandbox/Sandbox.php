<?php

namespace Drutiny\Sandbox;

use Drutiny\Audit\AuditInterface;

/**
 * Run check in an isolated environment.
 */
class Sandbox
{
    use ParameterTrait;
    use ReportingPeriodTrait;
    use Drutiny2xBackwardCompatibilityTrait;

    protected $target;
    protected $audit;

    public function __construct(AuditInterface $audit)
    {
        $this->audit = $audit;
        $this->setReportingPeriod($audit->getParameter('reporting_period_start', new \DateTime()), $audit->getParameter('reporting_period_end', new \DateTime()));
    }
}
