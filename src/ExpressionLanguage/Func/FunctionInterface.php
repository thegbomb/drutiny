<?php

namespace Drutiny\ExpressionLanguage\Func;

interface FunctionInterface {
  public function getName();

  public function getCompiler();

  public function getEvaluator();
}


 ?>
