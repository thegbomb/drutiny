<?php

namespace Drutiny\Report\Format;

use Drutiny\AssessmentInterface;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Profile;
use Drutiny\Report\BackportTemplateHacks;
use Drutiny\Report\Format;
use Drutiny\Report\FormatInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\TemplateWrapper;

class HTML extends Format
{
    // Support for 2.x templates.
    use BackportTemplateHacks;

    protected $format = 'html';
    protected $extension = 'html';


    public function setOptions(array $options = []):FormatInterface
    {
        if (!isset($options['content'])) {
            $options['content'] = $this->loadTwigTemplate('report/profile');
            // $options['content'] = $this->twig->load('report/profile.md.twig');
            // $options['content'] = Yaml::parseFile(dirname(__DIR__) . '/templates/content/profile.html.yml');
        }
        elseif (is_string($options['content'])) {
            $options['content'] = $this->twig->createTemplate($options['content']);
        }
        $options['template'] = $options['template'] ?? 'report/page.' . $this->getExtension() . '.twig';
        return parent::setOptions($options);
    }

    /**
     * Registered as a Twig filter to be used as: "Title here"|heading.
     */
    public static function filterSectionHeading(Environment $env, $heading)
    {
      return $env
        ->createTemplate('<h2 class="section-title" id="section_{{ heading | u.snake }}">{{ heading }}</h2>')
        ->render(['heading' => $heading]);
    }

    /**
     * Registered as a Twig filter to be used as: chart.foo|chart.
     */
    public static function filterChart(array $chart)
    {
      $class = 'chart-unprocessed';
      if (isset($chart['html-class'])) {
          $class .= ' '.$chart['html-class'];
      }
      $element = '<div class="'.$class.'" ';
      foreach ($chart as $name => $key) {
        $value = is_array($key) ? implode(',', $key) : $key;
        $element .= 'data-chart-'.$name . '="'.$value.'" ' ;
      }
      return $element . '></div>';
    }


    protected function prepareContent(Profile $profile, AssessmentInterface $assessment)
    {
      $variables = ['profile' => $profile, 'assessment' => $assessment];
      $sections = [];
      $markdown = $this->twig->getRuntime('Twig\Extra\Markdown\MarkdownRuntime');

      // In 3.x we support Twig TemplateWrappers to be passed directly
      // to the report format.
      if ($this->options['content'] instanceof TemplateWrapper) {
        foreach ($this->options['content']->getBlockNames() as $block){
          $sections[] = $markdown->convert($this->options['content']->renderBlock($block, $variables));
        }
        return $sections;
      }

      // Backward compatible 2.x Yaml style.
      foreach ($this->options['content'] as $section) {
        foreach ($section as $attribute => $value) {
          $template = $this->prefixTemplate($section[$attribute]);

          // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
          $template = $this->preMapDrutiny2Variables($template);

          // Convert from Mustache (supported in Drutiny 2.x) over to twig syntax.
          $template = $this->convertMustache2TwigSyntax($template);

          // Map the old Drutiny 2.x variables to the Drutiny 3.x versions.
          // $template = $this->mapDrutiny2toDrutiny3variables($template);

          try {
            // Convert template into a Twig template and render into HTML.
            $this->logger->debug("Creating section twig template for '$attribute' from string.");
            $template = $this->twig
              ->createTemplate($template)
              ->render($variables);
          }
          catch (\Twig\Error\Error $e) {
            $this->logger->error($e->getMessage());
            $source = $e->getSourceContext();
            $this->logger->info($source->getCode());
            $this->logger->debug("Original template: \n".$section[$attribute]);
          }

          // Convert any Markdown formatting over to HTML.
          $template = ($attribute == 'body') ? $markdown->convert($template) : $template;

          $section[$attribute] = $template;
        }
        $sections[] = $section;
      }
      return $sections;
    }
}
