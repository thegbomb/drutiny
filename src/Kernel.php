<?php

namespace Drutiny;

// use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
// use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

// use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel
{

  private const CONFIG_EXTS = '.{php,yaml,yml}';

  private $container;

  public function getContainer()
  {
    if (!$this->container) {
      return $this->initializeContainer();
    }
    return $this->container;
  }

  public function getProjectDir(): string
  {
      return \dirname(__DIR__);
  }

  /**
   * Initializes the service container.
   *
   * The cached version of the service container is used when fresh, otherwise the
   * container is built.
   */
  protected function initializeContainer()
  {
    $this->container = $this->buildContainer();
    $this->container->compile();
    return $this->container;
  }

  /**
     * Builds the service container.
     *
     * @return ContainerBuilder The compiled service container
     *
     * @throws \RuntimeException
     */
    protected function buildContainer()
    {
      $container = new ContainerBuilder();
      $container->addObjectResource($this);

      $loader = $this->getContainerLoader($container);

      $loader->load($this->getProjectDir().'/{drutiny}'.self::CONFIG_EXTS, 'glob');
      $loader->load($this->getProjectDir().'/vendor/*/{drutiny}'.self::CONFIG_EXTS, 'glob');
      return $container;
    }

  /**
     * Returns a loader for the container.
     *
     * @return DelegatingLoader The loader
     */
    protected function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator([$this->getProjectDir()]);
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }
}
