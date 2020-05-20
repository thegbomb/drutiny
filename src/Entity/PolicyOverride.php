<?php

namespace Drutiny\Entity;

use Drutiny\PolicyFactory;
use Drutiny\Entity\ExportableInterface;
use Drutiny\Entity\DataBag;
use Symfony\Component\Config\Definition\Processor;
use Drutiny\Config\PolicyOverrideConfiguration;

class PolicyOverride extends DataBag
{
    public function __construct(array $parameters = [])
    {
      $processor = new Processor();
      $configuration = new PolicyOverrideConfiguration();

      // Validate the new key is inline with the schema.
      $this->onSet(function ($k, $v) use ($processor, $configuration){
        $data = $this->all();
        $data[$k] = $v;
        $processor->processConfiguration($configuration, ['policy_override' => $data]);
      });

      $this->onAdd(function ($data) use ($processor, $configuration) {
        $data = array_merge($this->all(), $data);
        $processor->processConfiguration($configuration, ['policy_override' => $data]);
      });

      parent::__construct($parameters);
    }

    /**
     * Get the policy for the profile.
     */
    public function getPolicy(PolicyFactory $factory)
    {
        $policy = $factory->loadPolicyByName($this->get('name'));

        $overrides = $this->all();
        if (isset($overrides['severity'])) {
            $policy->setSeverity($overrides['severity']);
        }

        $overrides['parameters'] = $overrides['parameters'] ?? [];

        foreach ($overrides['parameters'] as $param => $value) {
            $policy->addParameter($param, $value);
        }
        return $policy;
    }
}
