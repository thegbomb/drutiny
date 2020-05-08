<?php

namespace Drutiny\Report;

use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigExtraMarkdownRuntimeLoader implements RuntimeLoaderInterface {
    public function load($class) {
        if (MarkdownRuntime::class === $class) {
            return new MarkdownRuntime(new DrutinyMarkdown());
        }
    }
}

 ?>
