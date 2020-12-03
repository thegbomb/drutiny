<?php

namespace Drutiny\Report\Format;

use Drutiny\AssessmentInterface;
use Drutiny\Profile;
use Drutiny\Report\FormatInterface;
use Twig\TemplateWrapper;

class HTML extends TwigFormat
{
    protected string $name = 'html';
    protected string $extension = 'html';


    public function setOptions(array $options = []):FormatInterface
    {
        if (!isset($options['content'])) {
            $options['content'] = $this->loadTwigTemplate('report/profile');
        }
        elseif (is_string($options['content'])) {
            $options['content'] = $this->twig->createTemplate($options['content']);
        }
        $options['template'] = $options['template'] ?? 'report/page.' . $this->getExtension() . '.twig';
        return parent::setOptions($options);
    }


    protected function prepareContent(Profile $profile, AssessmentInterface $assessment):array
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
