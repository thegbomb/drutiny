<?php

namespace Drutiny\ProfileSource;

use Drutiny\LanguageManager;
use Drutiny\Profile;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ProfileSource implements ProfileSourceInterface
{
    protected ContainerInterface $container;
    protected int $weight = 0;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getName():string;

    /**
     * {@inheritdoc}
     */
    abstract public function getList(LanguageManager $languageManager):array;

    /**
     * {@inheritdoc}
     */
    public function load(array $definition):Profile
    {
      return $this->container->get('profile.factory')->create($definition);
    }

  /**
   * {@inheritdoc}
   */
    public function getWeight():int
    {
        return $this->weight;
    }
}
