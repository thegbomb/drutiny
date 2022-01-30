<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\AssessmentInterface;
use Drutiny\Report\FormatInterface;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\ConsoleOutput;

class Terminal extends Markdown
{
    protected string $name = 'terminal';

    public function render(Profile $profile, AssessmentInterface $assessment):FormatInterface
    {
        parent::render($profile, $assessment);
        $this->buffer->write(self::format($this->buffer->fetch()));
        return $this;
    }

    public static function format(string $output)
    {
        return Renderer::createFromMarkdown($output);
    }

    public function write():iterable
    {
        $this->container->get('output')->write($this->buffer->fetch());
        yield 'terminal';
    }
}
