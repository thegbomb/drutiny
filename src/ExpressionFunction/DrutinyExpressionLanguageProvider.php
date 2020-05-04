<?php

namespace Drutiny\ExpressionFunction;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Drutiny\Container;
use Drutiny\Config;
use Drutiny\Sandbox\Sandbox;
use Doctrine\Common\Annotations\AnnotationReader;

class DrutinyExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    protected $sandbox;

    public function __construct(Sandbox $sandbox)
    {
        $this->sandbox = $sandbox;
    }

    public static function registry()
    {
        static $registry = [];
        if (empty($registry)) {
          // Build the vocabulary of expression syntax.
            $reader = new AnnotationReader();
            foreach (Config::get('ExpressionFunction') as $class) {
                $reflection = new \ReflectionClass($class);
                $is_compatible = $reflection->implementsInterface('Drutiny\ExpressionFunction\ExpressionFunctionInterface');
                if (!$is_compatible) {
                    continue;
                }
                $annotation = $reader->getClassAnnotation($reflection, 'Drutiny\Annotation\ExpressionSyntax');
                if (empty($annotation)) {
                    Container::getLogger()->warning("$class is missing @Drutiny\Annotation\ExpressionSyntax annotation.");
                    continue;
                }
                $registry[$annotation->name] = $class;
            }
        }
        return $registry;
    }

    public function getFunctions()
    {

        $functions = [];

        foreach ($this->registry() as $name => $class) {
            $functions[] = new ExpressionFunction(
                $name,
                // Compile function
                function () use ($class) {
                    $args = func_get_args();
                    array_unshift($args, $this->sandbox);
                    return call_user_func_array([$class, 'compile'], $args);
                },
                // Evaluate function
                function () use ($class) {
                    $args = func_get_args();
                    array_shift($args);
                    array_unshift($args, $this->sandbox);
                    return call_user_func_array([$class, 'evaluate'], $args);
                }
            );
        }
        return $functions;
    }
}
