<?php

namespace Drutiny\Audit\Drupal;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Run PHP lint over PHP files in a directory.
 *
 * @Param(
 *  name = "path",
 *  description = "The path where to lint PHP files.",
 *  type = "string",
 *  default = "%root"
 * )
 * @Token(
 *  name = "errors",
 *  description = "An array of parse errors found.",
 *  type = "array",
 *  default = {}
 * )
 */
class PhpLint extends Audit
{

  /**
   * @inheritdoc
   */
    public function audit(Sandbox $sandbox)
    {
      // find src/ -name \*.php -exec php -l {} \; 2>&1 | grep 'PHP Parse error:'
        $path = $sandbox->getParameter('path', '%root');
        $stat = $sandbox->drush(['format' => 'json'])->status();

        $path = strtr($path, $stat['%paths']);

        $errors = $sandbox->exec("find $path -name \*.php -exec php -l {} \; 2>&1 | grep 'PHP Parse error:' || true");
        $errors = array_filter(explode(PHP_EOL, $errors));
        $sandbox->setParameter('errors', $errors);
        return count($errors) == 0;
    }
}
