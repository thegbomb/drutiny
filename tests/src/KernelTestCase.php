<?php

namespace DrutinyTests;

use Drutiny\Console\Application;
use Drutiny\Kernel;
use Drutiny\Policy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;


abstract class KernelTestCase extends TestCase {

  protected $application;
  protected $output;
  protected $container;
  protected $profile;

  protected function setUp(): void
  {
      $kernel = new Kernel('phpunit');
      $kernel->addServicePath(
        str_replace(realpath($kernel->getProjectDir()), '', dirname(dirname(__FILE__))));
      $this->application = new Application($kernel, 'x.y.z');
      $this->application->setAutoExit(FALSE);
      $this->output = $kernel->getContainer()->get('output');
      $this->container = $kernel->getContainer();
      $this->profile = $this->container->get('profile.factory')->loadProfileByName('empty');
  }
}
