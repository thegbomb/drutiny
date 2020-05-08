<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\Assessment;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Yaml\Yaml;

class Terminal extends Markdown
{
    protected $format = 'terminal';

    public function render(Profile $profile, Assessment $assessment)
    {
        return (string) Renderer::createFromMarkdown(parent::render($profile, $assessment));
    }

    // public function renderMultiResult(array $variables)
    // {
    //     $markdown = parent::renderMultiResult($variables);
    //     return (string) Renderer::createFromMarkdown($markdown);
    // }
}
