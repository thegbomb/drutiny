<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\Assessment;
use Drutiny\Report\FormatInterface;
use Twig\Extra\Markdown\twig_html_to_markdown;

// class_exists('Twig\Extra\Markdown\MarkdownExtension');

class Markdown extends HTML
{
    protected $format = 'markdown';
    protected $extension = 'md';

    public function render(Profile $profile, Assessment $assessment)
    {
        $output = parent::render($profile, $assessment);
        $markdown = \Twig\Extra\Markdown\twig_html_to_markdown($output);
        $markdown = self::formatTables($markdown);

        $lines = explode(PHP_EOL, $markdown);
        $lines = array_filter($lines, function ($line) {
            return !preg_match(MarkdownHelper::CHART_REGEX, $line);
        });

        return implode(PHP_EOL, $lines);
    }


    // protected function preprocessMultiResult(Profile $profile, Target $target, array $results)
    // {
    //     $vars = parent::preprocessMultiResult($profile, $target, $results);
    //     $render = [
    //     'title' => $profile->getTitle(),
    //     'description' => $profile->getDescription(),
    //     'summary' => 'Report audits policies over ' . count($results) . ' sites.',
    //     'domain' => 'Multisite report'
    //     ];
    //     return array_merge($render, $vars);
    // }
    //
    // protected function renderMultiResult(array $variables)
    // {
    //     return $this->processRender($this->renderTemplate('multisite', $variables), $variables);
    // }
    //
    // protected function processRender($content, $render)
    // {
    //
    //   // Render the header/footer etc.
    //     $render['content'] = $content;
    //     $content = $this->renderTemplate($this->getTemplate(), $render);
    //
    //     return $this->formatTables($content);
    // }

    public static function formatTables($markdown)
    {
        $lines = explode(PHP_EOL, $markdown);
        $table = [
        'start' => null,
        'widths' => [],
        'rows' => [],
        ];

        foreach ($lines as $idx => $line) {
            if ($table['start'] === null) {
                if (strpos($line, ' | ') !== false) {
                    $table['start'] = $idx;
                } else {
                    continue;
                }
            } elseif (strpos($line, ' | ') === false) {
                foreach ($table['rows'] as $line_idx => $row) {
                    $widths = $table['widths'];

                    foreach ($row as $i => $value) {
                        $pad = array_search($line_idx, array_keys($table['rows'])) === 1 ? '-' : ' ';
                        $row[$i] = str_pad($value, $table['widths'][$i], $pad, STR_PAD_RIGHT);
                    }
                    $lines[$line_idx] = implode(' | ', $row);
                }

                $table['start']  = null;
                $table['widths'] = [];
                $table['rows']   = [];
                continue;
            }

            $cells = array_map('trim', explode('|', $line));

            foreach ($cells as $i => $value) {
                if (!isset($table['widths'][$i])) {
                    $table['widths'][$i] = strlen($value);
                } else {
                    $table['widths'][$i] = max($table['widths'][$i], strlen($value));
                }
            }
            $table['rows'][$idx] = $cells;
        }

        return implode(PHP_EOL, $lines);
    }
}
