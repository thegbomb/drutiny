<?php

namespace Drutiny\ExpressionFunction;

use Drutiny\Annotation\ExpressionSyntax;
use Drutiny\Sandbox\Sandbox;
use Drutiny\PolicySource\PolicySource;
use Drutiny\AuditResponse\NoAuditResponseFoundException;
use Drutiny\Container;
use Composer\Semver\Comparator;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * @ExpressionSyntax(
 * name = "policy",
 * usage = "policy('Test:Pass')",
 * description = "Returns the AuditReponse type of an audited policy."
 * )
 */
class PolicyExpressionFunction implements ExpressionFunctionInterface
{
    public static function compile(Sandbox $sandbox)
    {
        list($sandbox, $name, ) = func_get_args();

        return sprintf('Policy(%s)', $name);
    }

    public static function evaluate(Sandbox $sandbox)
    {
        list($sandbox, $name, ) = func_get_args();

        try {
            $response = $sandbox->getAssessment()->getPolicyResult($name);
        } catch (NoAuditResponseFoundException $e) {
            Container::getLogger()->info("Running policy " . $e->getPolicyName() . " audit inside dependency expression.");
            $policy = PolicySource::loadPolicyByName($e->getPolicyName());
            $box = new Sandbox($sandbox->getTarget(), $policy);
            $box->setReportingPeriodStart($sandbox->getReportingPeriodStart());
            $box->setReportingPeriodEnd($sandbox->getReportingPeriodEnd());
            $response = $box->run();

          // Omit policy from assessment.
        }
        Container::getLogger()->debug(sprintf('Policy(%s) returned "%s".', $name, $response->getType()));
        return $response->getType();
    }
}
