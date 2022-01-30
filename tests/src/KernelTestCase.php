<?php

namespace DrutinyTests;

use Drutiny\Console\Application;
use Drutiny\Kernel;
use Drutiny\Policy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;


abstract class KernelTestCase extends TestCase {

  protected $application;
  protected $output;
  protected $container;
  protected $profile;

  protected function setUp(): void
  {
      global $kernel;
      $kernel = new Kernel('phpunit');
      $kernel->addServicePath(
        str_replace(realpath($kernel->getProjectDir()), '', dirname(dirname(__FILE__))));
      $this->application = new Application($kernel, 'x.y.z');
      $this->application->setAutoExit(FALSE);
      $this->output = new BufferedOutput();
      $this->container = $kernel->getContainer();
      $this->container->set('output', $this->output);
      $this->profile = $this->container->get('profile.factory')->loadProfileByName('empty');
  }
}
