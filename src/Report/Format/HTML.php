<?php

namespace Drutiny\Report\Format;

use Drutiny\Assessment;
use Drutiny\Registry;
use Drutiny\Profile;
use Drutiny\Target\Target;
use TOC\MarkupFixer;
use TOC\TocGenerator;
use Drutiny\Report\Format\Menu\Renderer;
use Symfony\Component\Yaml\Yaml;


class HTML extends Markdown {


  public function __construct()
  {
    parent::__construct();
    $this->setFormat('html');
  }

  public function setOptions(array $options = [])
  {
    if (!isset($options['content'])) {
      $options['content'] = Yaml::parseFile(dirname(__DIR__) . '/templates/content/profile.html.yml');
    }
  }

  protected function preprocessResult(Profile $profile, Target $target, Assessment $assessment)
  {
    $render = parent::preprocessResult($profile, $target, $assessment);
    $parsedown = new MarkdownHelper();

    foreach ($render['remediations'] as &$remedy) {
      $remedy = $parsedown->text($remedy);
    }

    $markdown_fields = ['description', 'remediation', 'success', 'failure', 'warning'];

    // Unset Markdown renders.
    unset(
      $render['output_success'],
      $render['output_error'],
      $render['output_failure'],
      $render['output_warning'],
      $render['output_data'],
      $render['output_notice']
    );

    // Render any markdown into HTML for the report.
    foreach ($render['results'] as &$result) {
      foreach ($markdown_fields as $field) {
        $result[$field] = $parsedown->text($result[$field]);
      }

      $results_vars = ['result' => $result];
      $result_render = self::renderTemplate($result['type'], $results_vars);
      $render['output_' . $result['type']][] = $result_render;
      $render['output_' . $result['type'] . '_by_severity'][$result['severity_code']][] = $result_render;
      $result['rendered_result'] = $result_render;
      $render['types'][$result['type']] = $result['type'];
    }

    $render['types'] = array_values($render['types']);

    foreach ($render['types'] as $type) {
      ksort($render['output_' . $type . '_by_severity']);
      $render['output_' . $type . '_by_severity'] = call_user_func_array('array_merge', $render['output_' . $type . '_by_severity']);
    }

    $render['summary_table']  = self::renderTemplate('summary_table', $render);
    $render['appendix_table'] = self::renderTemplate('appendix_table', $render);
    $render['severity_stats'] = self::renderTemplate('severity_stats', $render);

    $engine = new \Mustache_Engine();
    $render['sections'] = [];
    foreach ($this->getContent() as $idx => $section) {
      try {
         $section = '## ' . $section['heading'] . PHP_EOL . $engine->render($section['body'], $render);
      }
      catch (\Mustache_Exception $e) {
        throw new \Exception("Error in " . __CLASS__ . ": " . $e->getMessage());
      }
      $render['sections'][] = $parsedown->text($section);
    }

    return $render;
  }

  protected function preprocessMultiResult(Profile $profile, Target $target, array $results)
  {
    $vars = parent::preprocessMultiResult($profile, $target, $results);
    $parsedown = new MarkdownHelper();
    foreach ($vars['by_policy'] as $name => $policy) {
      $vars['by_policy'][$name]['description'] = $parsedown->text($policy['description']);
      foreach ($policy['sites'] as $site => $outcome) {
        $vars['by_policy'][$name]['sites'][$site]['message'] = $parsedown->text($outcome['message']);
      }
    }
    return $vars;
  }

  protected function renderResult(array $variables) {
    return $this->processRender(self::renderTemplate('site', $variables), $variables);
  }

  protected function renderMultiResult(array $variables)
  {
    return $this->processRender(self::renderTemplate('multisite', $variables), $variables);
  }

  protected function processRender($content, $render)
  {
    // Preperation to generate Toc
    $markupFixer  = new MarkupFixer();
    $tocGenerator = new TocGenerator();

    // Render the site report.
    $content = $markupFixer->fix($content);

    // Insert span infront of headers to address navbar positioning.
    $content = preg_replace(
      '/(<h[2-4] id="([^"]+)")/',
      '<span class="navbar-pad"></span>$1',
      $content);

    // Table of Contents.
    $toc = self::renderTemplate('toc', [
      'table_of_contents' => $tocGenerator->getHtmlMenu($content, 2, 2)
    ]);

    // Render the header/footer etc.
    $render['content'] = $content;

    $options = [
      'branch_class'  => 'dropdown'
    ];

    $menu_renderer = new Renderer(new \Knp\Menu\Matcher\Matcher(), $options);

    $render['navbar'] = $tocGenerator->getHtmlMenu($content, 2, 3, $menu_renderer);
    $content = self::renderTemplate($this->getTemplate(), $render);

    // Hack to fix table styles in bootstrap theme.
    $content = strtr($content, [
      '<table>' => '<table class="table table-hover">',
      '#table_of_contents#' => $toc
    ]);

    return $content;
  }
}

 ?>
