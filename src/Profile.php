<?php

namespace Drutiny;

use Drutiny\Config\ProfileConfigurationTrait;
use Drutiny\Entity\PolicyOverride;
use Drutiny\Entity\StrictEntity;
use Drutiny\Sandbox\ReportingPeriodTrait;
use Drutiny\Entity\Exception\DataNotFoundException;


class Profile extends StrictEntity
{
    const ENTITY_NAME = 'profile';

    use ReportingPeriodTrait;
    use ProfileConfigurationTrait;

    protected bool $reportPerSite = false;
    protected Profile $parent;
    protected bool $compiled = false;
    protected array $policyOverrides = [];
    protected array $includes = [];
    protected array $bypassPropertyValidationOnSet = ['include', 'policies'];

    /**
     * {@inheritdoc}
     */
    protected function setPropertyData($property, $value)
    {
      switch ($property) {
        case 'policies':
          $this->setPolicies($value);
          break;

        case 'include':
          foreach ($value as $include) {
            $this->addInclude($include);
          }
          break;

        default:
          return parent::setPropertyData($property, $value);
      }
      return $this;
    }

    /**
     * Set the policy definitions.
     */
    public function setPolicies(array $policy_definitions):Profile
    {
      $this->dataBag->set('policies', []);
      return $this->addPolicies($policy_definitions);
    }

    /**
     * Append policy definitions.
     */
    public function addPolicies(array $policy_definitions):Profile
    {
      $new_policies = [];
      foreach ($policy_definitions as $idx => $definition) {
          $name = is_string($idx) ? $idx : $definition;
          $policy = new PolicyOverride($name);

          if (is_array($definition)) {
            foreach ($definition as $key => $value) {
              $policy->{$key} = $value;
            }
            if (!isset($policy->weight)) {
              $weight = array_search($idx, array_keys($policy_definitions)) + count($this->policies);
              $policy->weight = $weight;
            }
          }

          $new_policies[$name] = $policy;
      }

      $this->policyOverrides = array_merge($this->policyOverrides, $new_policies);

      $policies = array_map(function (PolicyOverride $policy) {
        return $policy->export();
      },
      $this->policyOverrides);

      $this->dataBag->set('policies', $policies);

      return $this;
    }

  /**
   * Add a PolicyDefinition to the profile.
   */
    public function getAllPolicyDefinitions():array
    {

      $list = array_filter($this->policyOverrides, function (PolicyOverride $policy_override) {
        return !in_array($policy_override->name, $this->excluded_policies ?? []);
      });

      // Sort $policies
      // 1. By weight. Lighter policies float to the top.
      // 2. By name, alphabetical sorting.
        uasort($list, function (PolicyOverride $a, PolicyOverride $b) {

          // 1. By weight. Lighter policies float to the top.
            if ($a->weight == $b->weight) {
                $alpha = [$a->name, $b->name];
                sort($alpha);
              // 2. By name, alphabetical sorting.
                return $alpha[0] == $a->name ? -1 : 1;
            }
            return $a->weight > $b->weight ? 1 : -1;
        });
        return $list;
    }

    /**
     * Add a Profile to the profile.
     */
    public function addInclude(Profile $profile)
    {
        // Detect recursive loop and skip include.
        if (!$profile->setParent($this)) {
          return $this;
        }

        $this->includes[$profile->uuid] = $profile->name;
        parent::setPropertyData('include', array_values($this->includes));

        $this->addPolicies($profile->getAllPolicyDefinitions());
        return $this;
    }

    public function hasParent():bool
    {
      return !empty($this->parent);
    }

    public function setParent(Profile $parent):bool
    {
      if (!$parent->hasAncestor($this)) {
        $this->parent = $parent;
        return true;
      }
      return false;
    }

    /**
     * Traverse ancestry of parent relationships to see if profile is in linage.
     */
    public function hasAncestor(Profile $ancestor):bool
    {
      if (!$this->hasParent()) {
        return false;
      }
      return $this->parent->name === $ancestor->name || $this->parent->hasAncestor($ancestor);
    }

    public function reportPerSite()
    {
        return $this->reportPerSite;
    }

    public function setReportPerSite($flag = true)
    {
        $this->reportPerSite = (bool) $flag;
        return $this;
    }

    public function export()
    {
        return $this->build()->dataBag->export();
    }

    /**
     * Compile the profile to validate it is complete.
     */
    public function build():Profile
    {
      if (!$this->compiled) {
          $this->validateAllPropertyData();
          $this->compiled = true;
      }
      return $this;
    }
}
