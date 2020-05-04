<?php

namespace Drutiny;

use Drutiny\ExpressionFunction\DrutinyExpressionLanguageProvider;
use Drutiny\Sandbox\Sandbox;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class ExpressionLanguage extends BaseExpressionLanguage
{
    public function __construct(Sandbox $sandbox)
    {
        // prepends the default provider to let users override it easily
        $providers[] = new DrutinyExpressionLanguageProvider($sandbox);

        parent::__construct(null, $providers);

        $this->addFunction(ExpressionFunction::fromPhp('in_array'));
        $this->addFunction(ExpressionFunction::fromPhp('array_key_exists'));
        $this->addFunction(ExpressionFunction::fromPhp('count'));
        $this->addFunction(ExpressionFunction::fromPhp('array_filter'));

        // Return an array from an array based on the value of one of its keyed properties.
        $this->addFunction(new ExpressionFunction(
            'array_select',
            // Compiler
            function () {
                list($input, $property, $value, $strict_match) = array_slice(func_get_args(), 1);
                return sprintf('array_select(<input_array>, "%s", "%s", "%b")', $property, $value, $strict_match);
            },
            // Evaluator
            function () {
                list($input, $property, $value, $strict_match) = array_slice(func_get_args(), 1);
                foreach ($input as $set) {
                    if (!isset($set[$property])) {
                        continue;
                    }
                    if ($strict_match && ($set[$property] == $value)) {
                        return $set;
                    }
                    if (!$strict_match && (strpos($set[$property], $value) !== false)) {
                        return $set;
                    }
                }
                return [
                $property => false,
                ];
            }
        ));

        /**
         * Filter an array of arrays by property.
         *
         * @param $array Array The input array.
         * @param $property String the property to compare in each array.
         * @param $match Mixed the value to compare with each property value.
         * @param $equals Bool Whether to do an equal comparison or "contains" comparison.
         */
        $this->addFunction(new ExpressionFunction(
            'filter',
            // Compiler
            function ($array, $property, $match, $equals) {
                list($array, $property, $match, $equals) = array_slice(func_get_args(), 1);
                return sprintf('filter(%s, "%s", "%s", "%s")', $array, $property, $match, $equals);
            },
            // Evaluator
            function ($array, $property, $match = true, $equals = true) {
                list($array, $property, $match, $equals) = array_slice(func_get_args(), 1);
                return array_filter($array, function ($value) use ($property, $match, $equals) {
                    if (!is_array($value)) {
                        return false;
                    }
                    $property_parts = explode('.', $property);
                    $ref = $value;
                    foreach ($property_parts as $key) {
                        if (!isset($ref[$key])) {
                            return false;
                        }
                        $ref = $ref[$key];
                    }

                    return $equals ? $ref == $match : strpos($ref, $match) !== false;
                });
            }
        ));
    }
}
