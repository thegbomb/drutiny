<?php

namespace DrutinyTests\Audit;

use Drutiny\Console\Application;
use Drutiny\Kernel;
use Drutiny\Policy;
use Drutiny\Sandbox\Sandbox;
use PHPUnit\Framework\TestCase;

class PolicyTest extends TestCase {

  protected $target;
  protected $application;
  protected $output;
  protected $container;

  protected function setUp(): void
  {
      $kernel = new Kernel('phpunit');
      $kernel->addServicePath(
        str_replace($kernel->getProjectDir(), '', dirname(dirname(__FILE__))));
      $this->application = new Application($kernel, 'x.y.z');
      $this->application->setAutoExit(FALSE);
      $this->output = $kernel->getContainer()->get('output');
      $this->container = $kernel->getContainer();
      $this->target = $this->container->get('target.factory')->create('@none');
  }

  public function testPass()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Pass');
    $sandbox = $this->container->get('sandbox')->create($this->target, $policy);

    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
  }

  public function testFail()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Fail');
    $sandbox = $this->container->get('sandbox')->create($this->target, $policy);

    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
  }

  public function testError()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Error');
    $sandbox = $this->container->get('sandbox')->create($this->target, $policy);

    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
    $this->assertTrue($response->hasError());
  }

  public function testWarning()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Warning');
    $sandbox = $this->container->get('sandbox')->create($this->target, $policy);

    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
    $this->assertTrue($response->hasWarning());
  }

  public function testNotApplicable()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:NA');
    $sandbox = $this->container->get('sandbox')->create($this->target, $policy);

    $response = $sandbox->run();
    $this->assertFalse($response->isSuccessful());
    $this->assertTrue($response->isNotApplicable());
  }

  public function testNotice()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Notice');
    $sandbox = $this->container->get('sandbox')->create($this->target, $policy);

    $response = $sandbox->run();
    $this->assertTrue($response->isSuccessful());
    $this->assertTrue($response->isNotice());
  }

}
