<?php

namespace Drutiny;

use Drutiny\Config\PolicyConfiguration;
use Drutiny\Policy\Dependency;
use Symfony\Component\Config\Definition\Processor;

class Policy
{
    const SEVERITY_LOW = 1;
    const SEVERITY_NORMAL = 2;
    const SEVERITY_HIGH = 4;
    const SEVERITY_CRITICAL = 8;

    protected $properties = [];
    protected $remediable = false;
    protected $dependencies = [];
    protected $severityCode;

  /**
   * @array list of object attributes that may be passed through the renderer.
   */
    protected $renderableProperties = [
    'title',
    'name',
    'description',
    'remediation',
    'success',
    'warning',
    'failure'
    ];

    public function getProperty($property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Make properties read-only attributes of object.
     */
    public function __get($property)
    {
      return $this->getProperty($property);
    }

    /**
     * Required for __get to work in twig templates.
     */
    public function __isset($property)
    {
      return $this->getProperty($property) !== NULL;
    }
  /**
   * Set policy property.
   */
    public function setProperty($property, $value)
    {
        return $this->setProperties([$property => $value]);
    }

    public function setProperties(array $new_properties = [])
    {
        $data = $this->properties;

        foreach ($new_properties as $property => $value) {
            $data[$property] = $value;
        }

        $processor = new Processor();
        $configuration = new PolicyConfiguration();

        $policyData = $processor->processConfiguration(
            $configuration,
            ['policy' => $data]
        );

        foreach ($policyData as $property => $value) {
            $this->properties[$property] = $value;
        }

        if (isset($properties['class'])) {
            $reflect = new \ReflectionClass($policyData['class']);
            $this->remediable = $reflect->implementsInterface('\Drutiny\RemediableInterface');
        }

        if (isset($properties['depends'])) {
            $builder = function ($depends) {
                return new Dependency($depends['expression'], $depends['on_fail']);
            };
            $this->dependencies = array_map($builder, $policyData['depends']);
        }

      // Map a severity value to its respective security code.
        if (isset($new_properties['severity'])) {
            switch ($policyData['severity']) {
                case 'low':
                    $this->severityCode = Policy::SEVERITY_LOW;
                    break;
                case 'normal':
                    $this->severityCode = Policy::SEVERITY_LOW;
                    break;
                case 'high':
                    $this->severityCode = Policy::SEVERITY_LOW;
                    break;
                case 'critical':
                    $this->severityCode = Policy::SEVERITY_LOW;
                    break;
            }
        }

        return $this;
    }

  /**
   * Get list of Drutiny\Policy\Dependency objects.
   */
    public function getDepends()
    {
        return $this->dependencies;
    }

    public function setSeverity($severity)
    {
        return $this->setProperty('severity', $severity);
    }

    public function getSeverity()
    {
        return $this->severityCode;
    }

    public function getSeverityName()
    {
        return $this->properties['severity'];
    }

    public function getParameter($key)
    {
        if (!isset($this->properties['parameters'][$key])) {
            throw new \Exception("$key is not an available parameter on policy {$this->properties['name']}.");
        }
        return $this->properties['parameters'][$key];
    }

    public function addParameter($key, $value)
    {
        $parameters = $this->properties['parameters'] ?? [];
        $parameters[$key] = $value;
        return $this->setProperty('parameters', $parameters);
    }

    public function export()
    {
        return $this->properties;
    }
}
