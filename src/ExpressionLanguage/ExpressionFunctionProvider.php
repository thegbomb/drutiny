<?php

namespace Drutiny\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions():iterable
    {
      $services = $this->container
      ->findTaggedServiceIds('drutiny.expression_language.function');
      foreach ($services as $id => $info) {
          yield $this->container->get($id);
      }
    }
}
