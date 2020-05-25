<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\Assessment;
use Drutiny\Report\FormatInterface;
use Twig\Extra\Markdown\twig_html_to_markdown;
use Twig\TemplateWrapper;

// class_exists('Twig\Extra\Markdown\MarkdownExtension');

class Markdown extends HTML
{
    protected $format = 'markdown';
    protected $extension = 'md';

    public function render(Profile $profile, Assessment $assessment)
    {
        $output = parent::render($profile, $assessment);
        // $markdown = \Twig\Extra\Markdown\twig_html_to_markdown($output);
        $markdown = self::formatTables($output);

        $lines = explode(PHP_EOL, $markdown);

        array_walk($lines, function (&$line) {
          $line = trim($line);
          return preg_replace('/^\s+/', '', $line);
        });

        $lines = array_filter($lines, function ($line) {
            return !preg_match(MarkdownHelper::CHART_REGEX, $line);
        });

        return implode(PHP_EOL, $lines);
    }

    protected function prepareContent(Profile $profile, Assessment $assessment)
    {
      $variables = ['profile' => $profile, 'assessment' => $assessment];
      $sections = [];

      // In 3.x we support Twig TemplateWrappers to be passed directly
      // to the report format.
      if ($this->options['content'] instanceof TemplateWrapper) {
        foreach ($this->options['content']->getBlockNames() as $block){
          $sections[] = $this->options['content']->renderBlock($block, $variables);
        }
        return $sections;
      }

      // Backward compatible 2.x Yaml style.
      foreach ($this->options['content'] as $section) {
        foreach ($section as $attribute => $value) {
          // Convert from Mustache (supported in Drutiny 2.x) over to twig syntax.
          $template = $this->convertMustache2TwigSyntax($section[$attribute]);

          // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
          $template = $this->mapDrutiny2toDrutiny3variables($template);

          try {
            // Convert template into a Twig template and render into HTML.
            $template = $this->twig
              ->createTemplate($template)
              ->render($variables);
          }
          catch (\Twig\Error\Error $e) {
            $this->logger->error($e->getMessage());
            $source = $e->getSourceContext();
            $this->logger->info($source->getCode());
          }

          $section[$attribute] = $template;
        }
        $sections[] = $section;
      }
      return $sections;
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
