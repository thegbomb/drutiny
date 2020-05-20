<?php

namespace Drutiny;

use Drutiny\Entity\PolicyOverride;
use Drutiny\Report\Format;
use Drutiny\Entity\ExportableInterface;
use Drutiny\Entity\DataBag;
use Psr\Log\LoggerInterface;

class Profile implements ExportableInterface
{
    use \Drutiny\Sandbox\ReportingPeriodTrait;

    protected $reportPerSite = false;
    protected $dataBag;
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
      $this->logger = $logger;
      $this->dataBag = new DataBag();
    }

    public static function create(LoggerInterface $logger)
    {
      return new static($logger);
    }

    /**
     * Make properties read-only attributes of object.
     */
    public function __get($property)
    {
      return $this->dataBag->get($property);
    }

    /**
     * Required for __get to work in twig templates.
     */
    public function __isset($property)
    {
      return $this->dataBag->has($property);
    }

    public function setProperties(array $data)
    {
      $data = array_merge($this->dataBag->all(), $data);
      $data['policies'] = $data['policies'] ?? new DataBag();
      if (is_array($data['policies'])) {
        $data['policies'] = new DataBag($data['policies']);
      }
      $keys = array_keys($data['policies']->all());
      foreach ($data['policies']->all() as $name => $policy_override) {
          $weight = array_search($name, $keys);
          if ($policy_override instanceof PolicyOverride) {
            $policy_override->set('weight', $weight);
            continue;
          }
          $policy_override['weight'] = $weight;
          $policy_override['name'] = $name;
          $data['policies']->set($name, new PolicyOverride($policy_override));
      }
      $this->dataBag->add($data);
      return $this;
    }

  /**
   * Add a PolicyDefinition to the profile.
   */
    public function getAllPolicyDefinitions()
    {
      $list = array_filter($this->policies->all(), function ($policy_override) {
        return !in_array($policy_override->name, $this->excluded_policies);
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
        $profile->setParent($this);

        $include = $this->include;
        $include[] = $profile->name;
        $this->dataBag->set('include', $include);

        $weight = count($this->getAllPolicyDefinitions());

        foreach ($profile->getAllPolicyDefinitions() as $policy_override) {
          // Do not override policies already specified, they take priority.
            if ($this->dataBag->get('policies')->get($policy_override->name)) {
                continue;
            }
            $policy_override->set('weight', ++$weight);
            $this->policies->set($policy_override->name, $policy_override);
        }
        return $this;
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
        return $this->dataBag->export();
    }
}
