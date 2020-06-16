<?php

namespace Drutiny;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class Kernel
{
    private const CONFIG_EXTS = '.{php,yaml,yml}';
    private $container;
    private $environment;
    private $loadingPaths = [];
    private $initialized = FALSE;

    public function __construct($environment)
    {
      $this->environment = $environment;
      $this->addServicePath($this->getProjectDir());
      $this->addServicePath('./vendor/*/*');
    }

    public function addServicePath($path)
    {
      if ($this->initialized) {
        throw new \RuntimeException("Cannot add $path as service path. Container already initialized.");
      }
      $this->loadingPaths[] = $path;
      return $this;
    }

    public function getContainer()
    {
        if (!$this->container) {
            return $this->initializeContainer();
        }
        return $this->container;
    }

    public function getProjectDir(): string
    {
        return DRUTINY_LIB;
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
        $this->initialized = TRUE;
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

        $container->addCompilerPass(new RegisterListenersPass('event_dispatcher', 'kernel.event_listener', 'drutiny.event_subscriber'));

        $container->setParameter('user_home_dir', getenv('HOME'));
        $container->setParameter('drutiny_core_dir', \dirname(__DIR__));

        foreach ($this->loadingPaths as $path) {
          $loading_path = [
            $this->getProjectDir(), $path, '{drutiny}'.self::CONFIG_EXTS,
          ];
          $loader->load(implode('/', $loading_path), 'glob');
        }


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
            new YamlFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }
}
