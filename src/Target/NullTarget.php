<?php

namespace Drutiny\Target;

/**
 * Target for parsing Drush aliases.
 */
class NullTarget extends Target implements TargetInterface
{
  /**
   * {@inheritdoc}
   */
  public function getId():string
  {
    return 'null';
  }

  /**
   * @inheritdoc
   * Implements Target::parse().
   */
  public function parse($alias):TargetInterface
  {
      return $this;
  }
}
