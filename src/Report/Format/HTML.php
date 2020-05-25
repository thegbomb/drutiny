<?php

namespace Drutiny\Report\Format;

use Drutiny\Assessment;
use Drutiny\Profile;
use Drutiny\Report\Format;
use Drutiny\Report\FormatInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\TemplateWrapper;
use Twig\Environment;

class HTML extends Format
{

    protected $format = 'html';
    protected $extension = 'html';


    public function setOptions(array $options = []):FormatInterface
    {
        if (!isset($options['content'])) {
            $options['content'] = $this->loadTwigTemplate('report/profile');
            // $options['content'] = $this->twig->load('report/profile.md.twig');
            // $options['content'] = Yaml::parseFile(dirname(__DIR__) . '/templates/content/profile.html.yml');
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


    protected function prepareContent(Profile $profile, Assessment $assessment)
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

          // Convert any Markdown formatting over to HTML.
          $template = ($attribute == 'body') ? $markdown->convert($template) : $template;

          $section[$attribute] = $template;
        }
        $sections[] = $section;
      }
      return $sections;
    }
}
