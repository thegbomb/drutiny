<?php

namespace Drutiny\AuditResponse;

use Drutiny\Policy;
use Drutiny\Audit;

/**
 * Class AuditResponse.
 *
 * @package Drutiny\AuditResponse
 */
class AuditResponse
{

    protected $policy;
    protected $state = Audit::NOT_APPLICABLE;
    protected $remediated = false;
    protected $tokens = [];

  /**
   * AuditResponse constructor.
   *
   * @param Policy $policy
   *   A policy object of type Drutiny\Policy.
   */
    public function __construct(Policy $policy)
    {
        $this->policy = $policy;
    }

  /**
   * Get the AudiResponse Policy.
   */
    public function getPolicy()
    {
        return $this->policy;
    }

  /**
   * Set the state of the response.
   */
    public function set($state = null, $tokens = [])
    {
        switch (true) {
            case ($state === Audit::SUCCESS):
            case ($state === Audit::PASS):
                $state = Audit::SUCCESS;
                break;

            case ($state === Audit::FAILURE):
            case ($state === Audit::FAIL):
                $state = Audit::FAIL;
                break;

            case ($state === Audit::NOT_APPLICABLE):
            case ($state === null):
                $state = Audit::NOT_APPLICABLE;
                break;

            case ($state === Audit::IRRELEVANT):
            case ($state === Audit::WARNING):
            case ($state === Audit::WARNING_FAIL):
            case ($state === Audit::NOTICE):
            case ($state === Audit::ERROR):
              // Do nothing. These are all ok.
                break;

            default:
                throw new AuditResponseException("Unknown state set in Audit Response: $state");
        }
        $this->setTokens($tokens);
        $this->state = $state;
        return $this;
    }

    public function setTokens(array $tokens = []) {
        $this->tokens = $tokens;
        return $this;
    }

    public function setToken($name, $value)
    {
      $this->tokens[$name] = $value;
      return $this;
    }

    public function getTokens()
    {
      return $this->tokens;
    }

  /**
   * Get the exception message if present.
   */
    public function getExceptionMessage()
    {
        return isset($this->tokens['exception']) ? $this->tokens['exception'] : '';
    }

  /**
   * Get the type of response based on policy type and audit response.
   */
    public function getType()
    {
        if ($this->isNotApplicable()) {
            return 'not-applicable';
        }
        if ($this->hasError()) {
            return 'error';
        }
        if ($this->isNotice()) {
            return 'notice';
        }
        if ($this->hasWarning()) {
            return 'warning';
        }
        $policy_type = $this->policy->getProperty('type');
        if ($policy_type == 'data') {
            return 'notice';
        }
        return $this->isSuccessful() ? 'success' : 'failure';
    }

  /**
   *
   */
    public function isSuccessful()
    {
        return $this->state === Audit::SUCCESS || $this->remediated || $this->isNotice() || $this->state === Audit::WARNING;
    }

    public function isFailure()
    {
      return $this->getType() == 'failure';
    }

  /**
   *
   */
    public function isNotice()
    {
        return $this->state === Audit::NOTICE;
    }

  /**
   *
   */
    public function hasWarning()
    {
        return $this->state === Audit::WARNING || $this->state === Audit::WARNING_FAIL;
    }

    public function isRemediated($set = null)
    {
        if (isset($set)) {
            $this->remediated = $set;
        }
        return $this->remediated;
    }

    public function hasError()
    {
        return $this->state === Audit::ERROR;
    }

    public function isNotApplicable()
    {
        return $this->state === Audit::NOT_APPLICABLE;
    }

    public function isIrrelevant()
    {
        return $this->state === Audit::IRRELEVANT;
    }

    public function getSeverity()
    {
        return $this->policy->getSeverityName();
    }

    public function getSeverityCode()
    {
        return $this->policy->getSeverity();
    }

  /**
   * Get the response based on the state outcome.
   *
   * @return string
   *   Translated description.
   */
    public function getSummary()
    {
        $summary = [];
        switch (true) {
            case ($this->state === Audit::NOT_APPLICABLE):
                $summary[] = "This policy is not applicable to this site.";
                break;

            case ($this->state === Audit::ERROR):
                $tokens = [
                'exception' => isset($this->tokens['exception']) ? $this->tokens['exception'] : 'Unknown exception occured.'
                ];
                $summary[] = strtr('Could not determine the state of ' . $this->getTitle() . ' due to an error:
```
exception
```', $tokens);
                break;

            case ($this->state === Audit::WARNING):
                $summary[] = $this->getWarning();
            case ($this->state === Audit::SUCCESS):
            case ($this->state === Audit::PASS):
            case ($this->state === Audit::NOTICE):
                $summary[] = $this->getSuccess();
                break;

            case ($this->state === Audit::WARNING_FAIL):
                $summary[] = $this->getWarning();
            case ($this->state === Audit::FAILURE):
            case ($this->state === Audit::FAIL):
                $summary[] = $this->getFailure();
                break;

            default:
                throw new AuditResponseException("Unknown AuditResponse state ({$this->state}). Cannot generate summary for '" . $this->getTitle() . "'.");
            break;
        }
        return implode(PHP_EOL, $summary);
    }
}
