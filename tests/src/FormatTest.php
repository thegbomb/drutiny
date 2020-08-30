<?php

namespace DrutinyTests;

use Drutiny\Console\Application;
use Drutiny\Kernel;
use Drutiny\Assessment;
use Drutiny\Sandbox\Sandbox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class FormatTest extends KernelTestCase {

  public function testBadFormatException()
  {
    $this->expectException(\InvalidArgumentException::class);
    $format = $this->container->get('format.factory')->create('doc');
  }

  public function testHtmlFormat()
  {
    $input = new ArrayInput([
      'command' => 'profile:run',
      'profile' => 'test',
      'target' => '@none',
      '--format' => 'html',
      '--report-filename' => 'stdout'
    ]);

    $code = $this->application->run($input, $this->output);
    $this->assertIsInt($code);
    $this->assertEquals(0, $code);
    $this->assertStringContainsString('<html', $this->output->fetch());
  }

  public function testMarkdownFormat()
  {
    $input = new ArrayInput([
      'command' => 'profile:run',
      'profile' => 'test',
      'target' => '@none',
      '--format' => 'markdown',
      '--report-filename' => 'stdout'
    ]);

    $code = $this->application->run($input, $this->output);
    $this->assertIsInt($code);
    $this->assertEquals(0, $code);
    $this->assertStringContainsString('# ', $this->output->fetch());
  }

  public function testJsonFormat()
  {
    $input = new ArrayInput([
      'command' => 'profile:run',
      'profile' => 'test',
      'target' => '@none',
      '--format' => 'json',
      '-o' => 'stdout'
    ]);

    $code = $this->application->run($input, $this->output);
    $this->assertIsInt($code);
    $this->assertEquals(0, $code);
    $this->assertNotEmpty($json = $this->output->fetch());
    $object = json_decode($json);
    $this->assertTrue(is_object($object));
  }
}
