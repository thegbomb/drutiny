<?php

namespace Drutiny\Target;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TargetFactory implements ContainerAwareInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    public function create(string $target_reference, ?string $uri = null):TargetInterface
    {
      // By default, assume a target is using drush.
        $target_name = 'drush';
        $target_data = $target_reference;

      // If a colon is used, then an alternate target maybe used.
        if (strpos($target_reference, ':') !== false) {
            list($target_name, $target_data) = explode(':', $target_reference, 2);
        }

        $target = $this->container->get("target.$target_name");
        $target->parse($target_data, $uri);
        $target->setTargetName($target_reference);

        $this->container->set('target', $target);

        return $target;
    }
}
