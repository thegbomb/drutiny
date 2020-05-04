<?php

namespace Drutiny\Report\Format;

use Parsedown;

class MarkdownHelper extends Parsedown
{

    const CHART_REGEX = '/\[\[\[(([a-z\-]+)="([^"]*)" ?)+\]\]\]/';

  // Remove Code as a block type. This prevents code blocks occuring
  // from indentation as it becomes confusing in YAML template files.
    protected $unmarkedBlockTypes = array('Foo');

    public function __construct()
    {
        $this->InlineTypes['['][] = 'Chart';
    }

  // $unmarkedBlockTypes cannot be empty due to a parsing bug so blockFoo() must
  // be defined.
    protected function blockFoo()
    {
        return;
    }

    protected function inlineChart($Excerpt)
    {
        if (preg_match(self::CHART_REGEX, $Excerpt['text'], $matches)) {
            $element = [];
            $element['extent'] = strlen($matches[0]);
            $element['element'] = [
            'name' => 'div',
            'text' => '',
            'attributes' => [
            'class' => 'chart-unprocessed',
            ]
            ];

            preg_match_all('/([a-z\-]+)="([^"]*)"/', $Excerpt['text'], $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $element['element']['attributes']['data-chart-' . $match[1]] = $match[2];
            }
            return $element;
        }
    }
}
