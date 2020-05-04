<?php

namespace Drutiny\Policy;

use Symfony\Component\Yaml\Yaml;
use RomaricDrigon\MetaYaml\Exception\NodeValidatorException;

class ValidationException extends \Exception
{

    public function __construct(array $info, NodeValidatorException $e)
    {
        $message = [];
        $message[] = "Policy data failed validation. Please ensure the policy data is compatible with the version of Drutiny you're using.";
        $message[] = 'Validation error: ' . $e->getMessage();
        $message[] = Yaml::dump(['policy information' => $info]);

        parent::__construct(implode(PHP_EOL, $message));
    }
}
