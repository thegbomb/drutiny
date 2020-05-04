<?php

namespace Drutiny;

use Drutiny\Profile\PolicyDefinition;
use Drutiny\Profile\ProfileSource;
use Drutiny\Report\Format;
use Symfony\Component\Yaml\Yaml;

class Profile {
  use \Drutiny\Sandbox\ReportingPeriodTrait;

  /**
   * Title of the Profile.
   *
   * @var string
   */
  protected $title;

  /**
   * Machine name of the Profile.
   *
   * @var string
   */
  protected $name;


  /**
   * Description of the Profile.
   *
   * @var string
   */
  protected $description;

  /**
   * Filepath location of where the profile file is.
   *
   * @var string
   */
  protected $filepath;

  /**
   * A list of other \Drutiny\Profile\ProfileDefinition objects to include.
   *
   * @var array
   */
  protected $policies = [];

  /**
   * A list of policy names, presumably inherited, to exclude.
   */
  protected $excludedPolicies = [];

  /**
   * A list of other \Drutiny\Profile objects to include.
   *
   * @var array
   */
  protected $include = [];

  /**
   * If profile is included by another profile then this property points to that profile.
   *
   * @var object Profile.
   */
  protected $parent;


  /**
   * Keyed array of \Drutiny\Report\FormatOptions.
   *
   * @var array
   */
  protected $format = [];

  /**
   * Flag to render multisite reports into single site reports also.
   *
   * @var boolean
   */
  protected $reportPerSite = FALSE;

  /**
   * Load a profile from file.
   *
   * @var $filepath string
   */
  public static function loadFromFile($filepath)
  {
    $info = Yaml::parseFile($filepath);
    $name = str_replace('.profile.yml', '', pathinfo($filepath, PATHINFO_BASENAME));

    $profile = new static();
    $profile->setTitle($info['title'])
            ->setName($name)
            ->setFilepath($filepath);

    if (isset($info['description'])) {
      $profile->setDescription($info['description']);
    }

    if (isset($info['policies'])) {
      $v21_keys = ['parameters', 'severity'];
      foreach ($info['policies'] as $name => $metadata) {
        // Check for v2.0.x style profiles.
        if (!empty($metadata) && !count(array_intersect($v21_keys, array_keys($metadata)))) {
          throw new \Exception("{$info['title']} is a v2.0.x profile. Please upgrade $filepath to v2.2.x schema.");
        }
        $weight = array_search($name, array_keys($info['policies']));
        $profile->addPolicyDefinition(PolicyDefinition::createFromProfile($name, $weight, $metadata));
      }
    }

    if (isset($info['excluded_policies']) && is_array($info['excluded_policies'])) {
      $profile->addExcludedPolicies($info['excluded_policies']);
    }

    if (isset($info['include'])) {
      foreach ($info['include'] as $name) {
        $include = ProfileSource::loadProfileByName($name);
        $profile->addInclude($include);
      }
    }

    if (isset($info['format'])) {
      foreach ($info['format'] as $format => $options) {
        $profile->addFormatOptions($format, $options);
      }
    }

    return $profile;
  }

  /**
   * Add a FormatOptions to the profile.
   */
  public function addFormatOptions($format, $options)
  {
    $this->format[$format] = $options;
    return $this;
  }

  /**
   * Get a FormatOptions to the profile.
   */
  public function getFormatOptions($format)
  {
    return $this->format[$format] ?? [];
  }

  /**
   * Add a PolicyDefinition to the profile.
   */
  public function addPolicyDefinition(PolicyDefinition $definition)
  {
    // Do not include excluded dependencies
    if (!in_array($definition->getName(), $this->excludedPolicies)) {
      $this->policies[$definition->getName()] = $definition;
    }
    return $this;
  }

  public function addExcludedPolicies(array $excluded_policies)
  {
    $this->excludedPolicies = array_unique(array_merge($this->excludedPolicies, $excluded_policies));
    return $this;
  }

  /**
   * Add a PolicyDefinition to the profile.
   */
  public function getPolicyDefinition($name)
  {
    return isset($this->policies[$name]) ? $this->policies[$name] : FALSE;
  }

  /**
   * Add a PolicyDefinition to the profile.
   */
  public function getAllPolicyDefinitions()
  {

    // Sort $policies
    // 1. By weight. Lighter policies float to the top.
    // 2. By name, alphabetical sorting.
    uasort($this->policies, function (PolicyDefinition $a, PolicyDefinition $b) {

      // 1. By weight. Lighter policies float to the top.
      if ($a->getWeight() == $b->getWeight()) {
        $alpha = [$a->getName(), $b->getName()];
        sort($alpha);
        // 2. By name, alphabetical sorting.
        return $alpha[0] == $a->getName() ? -1 : 1;
      }
      return $a->getWeight() > $b->getWeight() ? 1 : -1;
    });
    return $this->policies;
  }

  /**
   * Get the profile title.
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set the title of the profile.
   */
  public function setTitle($title)
  {
    $this->title = $title;
    return $this;
  }

  /**
   * Get the profile Name.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set the Name of the profile.
   */
  public function setName($name)
  {
    $this->name = $name;
    return $this;
  }

  /**
   * Get the profile Name.
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Set the Name of the profile.
   */
  public function setDescription($description)
  {
    $this->description = $description;
    return $this;
  }

  /**
   * Get the filepath.
   */
  public function getFilepath()
  {
    return $this->filepath;
  }

  /**
   * Set the Name of the profile.
   */
  public function setFilepath($filepath)
  {
    $this->filepath = $filepath;
    return $this;
  }

  /**
   * Add a Profile to the profile.
   */
  public function addInclude(Profile $profile)
  {
    $profile->setParent($this);
    $this->include[$profile->getName()] = $profile;
    foreach ($profile->getAllPolicyDefinitions() as $policy) {
      // Do not override policies already specified, they take priority.
      if ($this->getPolicyDefinition($policy->getName())) {
        continue;
      }
      $this->addPolicyDefinition($policy);
    }
    return $this;
  }

  /**
   * Return a specific included profile.
   */
  public function getInclude($name)
  {
    return isset($this->include[$name]) ? $this->include[$name] : FALSE;
  }

  /**
   * Return an array of profiles included in this profile.
   */
  public function getIncludes()
  {
    return $this->include;
  }

  /**
   * Add a Profile to the profile.
   */
  public function setParent(Profile $parent)
  {
    // Ensure parent doesn't already have this profile loaded.
    // This prevents recursive looping.
    if (!$parent->getParent($this->getName())) {
      $this->parent = $parent;
      return $this;
    }
    throw new \Exception($this->getName() . ' already found in profile lineage.');
  }

  /**
   * Find a parent in the tree of parent profiles.
   */
  public function getParent($name = NULL)
  {
    if (!$this->parent) {
      return FALSE;
    }
    if ($name) {
      if ($this->parent->getName() == $name) {
        return $this->parent;
      }
      if ($parent = $this->parent->getInclude($name)) {
        return $parent;
      }
      // Recurse up the tree to find if the parent is in the tree.
      return $this->parent->getParent($name);
    }
    return $this->parent;
  }

  public function reportPerSite()
  {
    return $this->reportPerSite;
  }

  public function setReportPerSite($flag = TRUE)
  {
    $this->reportPerSite = (bool) $flag;
    return $this;
  }

  public function dump()
  {
    $format = $this->getFormatOption('html');
    $export = [
      'title' => $this->getTitle(),
      'name' => $this->getName(),
      'description' => $this->getDescription(),
      'policies' => $this->dumpPolicyDefinitions(),
      'excluded_policies' => $this->excludedPolicies,
      'include' => array_keys($this->getIncludes()),
    //  'content' => $format->getContent(),
    ];
    if ($template = $format->getTemplate()) {
      $export['format']['html']['template'] = $template;
    }
    if ($html = $format->getContent()) {
      $export['format']['html']['content'] = $html;
    }
    return array_filter($export);
  }

  protected function dumpPolicyDefinitions()
  {
    $list = [];
    foreach ($this->policies as $name => $policy) {
      $list[$name] = $policy->getProfileMetadata();
    }
    return $list;
  }
}

 ?>
