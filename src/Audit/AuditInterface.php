<?php

namespace Drutiny\Audit;

use Drutiny\Sandbox\Sandbox;

/**
 * Audit interface.
 */
interface AuditInterface
{
  /**
   * The policy successfully passed the audit.
   */
    const SUCCESS = true;

  /**
   * Same as Audit::SUCCESS
   */
    const PASS = 1;

  /**
   * Same as Audit::FAILURE
   */
    const FAIL = false;

  /**
   * The policy failed to pass the audit.
   */
    const FAILURE = 0;

  /**
   * An audit returned non-assertive information.
   */
    const NOTICE = 2;

  /**
   * An audit returned success with a warning.
   */
    const WARNING = 4;

  /**
   * An audit returned failure with a warning.
   */
    const WARNING_FAIL = 8;

  /**
   * An audit did not complete and returned an error.
   */
    const ERROR = 16;

  /**
   * An audit was not applicable to the target.
   */
    const NOT_APPLICABLE = -1;

  /**
   * An audit that is irrelevant to the assessment and should be omitted.
   */
    const IRRELEVANT = -2;

    const PARAMETER_REQUIRED = 1;
    const PARAMETER_OPTIONAL = 2;
    const PARAMETER_IS_ARRAY = 4;

    public function configure();
    public function audit(Sandbox $sandbox);
    public function execute(Sandbox $sandbox);
}
