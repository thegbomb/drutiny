<?php

namespace Drutiny\Entity;

use Drutiny\PolicyFactory;
use Drutiny\Entity\ExportableInterface;
use Drutiny\Entity\DataBag;
use Symfony\Component\Config\Definition\Processor;
use Drutiny\Config\PolicyOverrideConfiguration;

class PolicyOverride extends EventDispatchedDataBag
{

    public function validate()
    {
        $processor = new Processor();
        $configuration = new PolicyOverrideConfiguration();
        $processor->processConfiguration($configuration, ['policy_override' => $this->all()]);
    }

    /**
     * Get the policy for the profile.
     */
    public function getPolicy(PolicyFactory $factory)
    {
        $this->validate();
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
