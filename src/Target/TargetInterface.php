<?php

namespace Drutiny\Target;
use Drutiny\Annotation\Metadata;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Definition of a Target.
 */
interface TargetInterface extends ContainerAwareInterface {

  /**
   * Parse the target data passed in.
   * @param $target_data string to parse.
   */
  public function parse($target_data);

  /**
   * Hook to validate the target is auditable.
   */
  public function validate();

  /**
   * Provide a URI to represent the Target.
   */
  public function uri();

  /**
   * Set the URI
   */
  public function setUri($uri);

  /**
   * Execute a shell command against the target.
   */
  public function exec($command, $args = []);

  /**
   * Target URI
   * @Metadata(name = "uri")
   */
  public function metadataUri();

  /**
   * Target Domain
   * @Metadata(name = "domain")
   */
  public function metadataDomain();
}
