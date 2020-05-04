<?php

namespace Drutiny\Profile;

use Drutiny\Policy;
use Drutiny\PolicyFactory;

class PolicyDefinition
{

  /**
   * Name of the poilcy.
   *
   * @var string
   */
    protected $name;

  /**
   * Weight of the policy in the order of the Profile.
   *
   * @var int
   */
    protected $weight = 0;

  /**
   * A list of PolicyDefinition objects that should be ordered before this one.
   *
   * @var array
   */
    protected $positionBefore = [];

  /**
   * Parameters to set on the policy.
   *
   * @var array
   */
    protected $parameters = [];

  /**
   * Severity of policy as defined by the profile.
   *
   * @var string
   */
    protected $severity = 'normal';

  /**
   * Build a PolicyDefinition from Profile input.
   *
   * @var $name string
   * @var $definition array
   */
    public static function createFromProfile($name, $weight = 0, $definition = [])
    {
        $policyDefinition = new static();
        $policyDefinition->setName($name)
                     ->setWeight($weight);

        if (isset($definition['parameters'])) {
            $policyDefinition->setParameters($definition['parameters']);
        }

        if (isset($definition['severity'])) {
            $policyDefinition->setSeverity($definition['severity']);
        }

        return $policyDefinition;
    }

  /**
   * Get the name of the policy.
   */
    public function getName()
    {
        return $this->name;
    }

  /**
   * Set the name of the policy.
   */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

  /**
   * Get the weight of the policy.
   */
    public function getWeight()
    {
        return $this->weight;
    }

  /**
   * Set the weight of the policy.
   */
    public function setWeight($weight)
    {
        $this->weight = (int) $weight;
        return $this;
    }

    public function setParameters(Array $params)
    {
        $this->parameters = $params;
    }

  /**
   * Get the policy for the profile.
   */
    public function getPolicy(PolicyFactory $factory)
    {

        $policy = $factory->loadPolicyByName($this->getName());
        if ($this->getSeverity() !== null) {
            $policy->setSeverity($this->getSeverity());
        }

        foreach ($this->parameters as $param => $value) {
            $policy->addParameter($param, $value);
        }
        return $policy;
    }

  /**
   * Track a policy dependency as a policy definition.
   */
    public function setDependencyPolicyName($name)
    {
        $this->positionBefore[$name] = self::createFromProfile($name, $this->getWeight());
        return $this;
    }

  /**
   * Get all dependencies.
   */
    public function getDependencyPolicyDefinitions()
    {
        return $this->positionBefore;
    }

    public function getProfileMetadata()
    {
        return array_filter([
        'parameters' => $this->parameters,
        'severity' => $this->severity
        ]);
    }

    public function setSeverity($severity)
    {
        $this->severity = $severity;
        return $this;
    }

    public function getSeverity()
    {
        return $this->severity;
    }
}
