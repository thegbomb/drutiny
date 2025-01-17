<?php

namespace Drutiny\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Yaml\Yaml;
use Twig\Error\RuntimeError;

/**
 * Audit gathered data.
 */
class AbstractAnalysis extends Audit
{
  public function configure()
  {
    $this->addParameter(
        'expression',
        static::PARAMETER_REQUIRED,
        'The expression language to evaluate. See https://symfony.com/doc/current/components/expression_language/syntax.html'
      )
      ->addParameter(
        'warning',
        static::PARAMETER_OPTIONAL,
        'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
        'false'
      )
      ->addParameter(
        'variables',
        static::PARAMETER_OPTIONAL,
        'A keyed array of expressions to set variables before evaluating the passing expression.',
        []
      )
      ->addParameter(
        'syntax',
        static::PARAMETER_OPTIONAL,
        'expression_language or twig',
        'expression_language'
      )
      ->addParameter(
        'not_applicable',
        static::PARAMETER_OPTIONAL,
        'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
        'false'
      );
  }

  /**
   * Gather analysis data to audit.
   */
    protected function gather(Sandbox $sandbox) {}

    final public function audit(Sandbox $sandbox)
    {
        $this->gather($sandbox);
        $syntax = $this->getParameter('syntax', 'expression_language');

        if ($expression = $this->getParameter('not_applicable', 'false')) {
            $this->logger->debug(__CLASS__ . ':INAPPLICABILITY ' . $expression);
            if ($this->evaluate($expression, $syntax)) {
                return self::NOT_APPLICABLE;
            }
        }

        foreach ($this->getParameter('variables',[]) as $key => $value) {
          try {
            $this->logger->debug(__CLASS__ . ':VARIABLE('.$key.'): ' . $value);
            $this->set($key, $this->evaluate($value, $syntax));
          }
          catch (RuntimeError $e)
          {
            throw new \Exception("Failed to create key: $key. Encountered Twig runtime error: " . $e->getMessage());
          }
        }

        $expression = $this->getParameter('expression', 'true');
        $this->logger->debug(__CLASS__ . ':EXPRESSION: ' . $expression);
        $output = $this->evaluate($expression, $syntax);
        $this->logger->debug(__CLASS__ . ':EVALUATION: ' . json_encode($output));
        return $output;
    }
}
