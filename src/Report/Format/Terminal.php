<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\Registry;
use Drutiny\Target\Target;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Yaml\Yaml;

class Terminal extends Markdown
{

    public function renderResult(array $variables)
    {
        $markdown = parent::renderResult($variables);
        return (string) Renderer::createFromMarkdown($markdown);
    }

    public function renderMultiResult(array $variables)
    {
        $markdown = parent::renderMultiResult($variables);
        return (string) Renderer::createFromMarkdown($markdown);
    }
}
