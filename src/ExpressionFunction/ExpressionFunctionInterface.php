<?php

namespace Drutiny\ExpressionFunction;

use Drutiny\Sandbox\Sandbox;

interface ExpressionFunctionInterface
{

  /**
   * Callback for ExpressionFunction compiler.
   * @see https://symfony.com/doc/current/components/expression_language/extending.html
   */
    public static function compile(Sandbox $sandbox);

  /**
   * Callback for ExpressionFunction evaluator.
   * @see https://symfony.com/doc/current/components/expression_language/extending.html
   */
    public static function evaluate(Sandbox $sandbox);
}
