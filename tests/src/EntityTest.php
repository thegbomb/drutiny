<?php

namespace DrutinyTests\Audit;

use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Audit;
use Drutiny\Console\Application;
use Drutiny\Kernel;
use Drutiny\Policy;
use Drutiny\Sandbox\Sandbox;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase {

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
  }

  public function testPolicyObjectUsage()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Pass');
    $this->assertEquals($policy->name, 'Test:Pass');

    // Testing dynamic assignment to policy properties.
    $policy->name = 'Test:Test';
    $this->assertEquals($policy->name, 'Test:Test');

    // Testing dynamic assignment of parameters.
    $policy->addParameters([
      'foo' => 'bar',
      'baz' => 'gat'
    ]);

    $this->assertEquals($policy->getParameter('foo'), 'bar');
    $this->assertEquals($policy->parameters['baz'], 'gat');

    // Confirm the export can be imported verbatim.
    $policy2 = new Policy();
    $policy2->setProperties($policy->export());

    $this->assertEquals($policy->title, $policy2->title);
  }

  public function testTargetObjectUsage()
  {
      $target = $this->container->get('target.factory')->create('@none');
      $target->setUri('bar');
      $this->assertEquals($target->getProperty('uri'), 'bar');
  }

  public function testAuditResponse()
  {
    $policy = $this->container->get('policy.factory')->loadPolicyByName('Test:Pass');
    $response = new AuditResponse($policy);
    $response->set(Audit::SUCCESS, [
      'foo' => 'bar',
    ]);
    $this->assertArrayHasKey('foo', $response->getTokens());
    $this->assertContains('bar', $response->getTokens());
    $this->assertTrue($response->isSuccessful());

    $export = $response->export();
    $export['state'] = Audit::FAIL;
    $response->import($export);

    $this->assertFalse($response->isSuccessful());
    $this->assertFalse($response->hasError());

    $this->assertIsString($sleep_data = $response->serialize());

    $export = unserialize($sleep_data);
    $export['state'] = Audit::ERROR;
    $sleep_data = serialize($export);

    $response->unserialize($sleep_data);

    $this->assertFalse($response->isSuccessful());
    $this->assertTrue($response->hasError());
  }
}
