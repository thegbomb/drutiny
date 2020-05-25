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
        parent::render($profile, $assessment);
        $this->buffer->write(Renderer::createFromMarkdown($this->buffer->fetch()));
        return $this;
    }
}
