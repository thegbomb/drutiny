<?php

namespace Drutiny\Target;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TargetFactory implements ContainerAwareInterface {
  use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

  public function __construct(ContainerInterface $container) {
    $this->setContainer($container);
  }

  public function create($target)
  {
    // By default, assume a target is using drush.
    $target_name = 'drush';
    $target_data = $target;

    // If a colon is used, then an alternate target maybe used.
    if (strpos($target, ':') !== FALSE) {
      list($target_name, $target_data) = explode(':', $target, 2);
    }

    $target = $this->container->get("target.$target_name");
    $target->setContainer($this->container);
    $target->parse($target_data);

    $this->container->set('target', $target);

    return $target;
  }
}

 ?>
