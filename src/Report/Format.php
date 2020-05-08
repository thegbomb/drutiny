<?php

namespace Drutiny\Report;

use Drutiny\Assessment;
use Drutiny\Profile;
use Drutiny\Console\Verbosity;
use Symfony\Component\Console\Output\BufferedOutput;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class Format implements FormatInterface
{

    protected $format = 'unknown';
    protected $extension = 'txt';
    protected $output;
    protected $twig;
    protected $options = [];
    protected $logger;

    public function __construct(Verbosity $verbosity, Environment $twig, LoggerInterface $logger)
    {
        $this->output = new BufferedOutput($verbosity->get(), true);
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function render(Profile $profile, Assessment $assessment)
    {
        try {
          return $this->twig->render($this->options['template'], [
            'profile' => $profile,
            'assessment' => $assessment,
            'sections' => $this->prepareContent($profile, $assessment),
          ]);
        }
        catch (\Twig\Error\Error $e) {
          $this->logger->error($e->getMessage());
          $source = $e->getSourceContext();
          $this->logger->info($source->getCode());
          throw $e;
        }
    }

    abstract protected function prepareContent(Profile $profile, Assessment $assessment);

    public function setOptions(array $options = []):FormatInterface
    {
      $this->options = $options;
      return $this;
    }

    public function getExtension():string
    {
      return $this->extension;
    }

  /**
   * Get the profile title.
   */
    public function getFormat()
    {
        return $this->format;
    }

  /**
   * Set the title of the profile.
   */
    protected function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

  /**
   * Get the profile title.
   */
    public function getOutput()
    {
        return $this->output;
    }

    public function getName():string
    {
        return $this->format;
    }

    protected function mapDrutiny2toDrutiny3variables($template)
    {
      return strtr($template, [
        'reporting_period_start' => "assessment.reportingPeriodStart.format('Y-m-d H:i:s e')",
        'reporting_period_end' => "assessment.reportingPeriodEnd.format('Y-m-d H:i:s e')",

        '__failures' => 'assessment.results|filter(r => r.isFailure) is not empty',
        '__notices' => 'assessment.results|filter(r => r.isNotice) is not empty',
        '__warnings' => 'assessment.results|filter(r => r.hasWarning) is not empty',
        '__errors' => 'assessment.results|filter(r => r.hasError) is not empty',
        '__passes' => 'assessment.results|filter(r => r.isSuccessful) is not empty',

        '{{ appendix_table |raw }}' => "{% include 'report/page/appendix_table.html.twig' %}",
        '{{ severity_stats |raw }}' => "{% include 'report/page/severity_stats.html.twig' %}",
        '{{ summary_table |raw }}' => "{% include 'report/page/summary_table.html.twig' %}",

        '{% for var0remediations in remediations %}' => "{% for response in assessment.results|filter(r => r.isFailure) %}",
        '{{ var0remediations |raw }}' => "{% with response.tokens %}{{ include(template_from_string(response.policy.remediation)) | markdown_to_html }}{% endwith %}",

        'var1output_failure in output_failure' => "response in assessment.results|filter(r => r.isFailure)",
        '{{ var1output_failure|raw }}' => "{% include 'report/policy/failure.html.twig' with {'result': response } %}",

        'var1output_warning in output_warning' => "response in assessment.results|filter(r => r.hasWarning)",
        '{{ var1output_warning|raw }}' => "{% include 'report/policy/warning.html.twig' with {'result': response } %}",

        'var1output_notice in output_notice' => "response in assessment.results|filter(r => r.isNotice)",
        '{{ var1output_notice|raw }}' => "{% include 'report/policy/notice.html.twig' with {'result': response } %}",

        'var1output_error in output_error' => "response in assessment.results|filter(r => r.hasError)",
        '{{ var1output_error|raw }}' => "{% include 'report/policy/error.html.twig' with {'result': response } %}",

        'var1output_success in output_success' => "response in assessment.results|filter(r => r.isSuccessful)",
        '{{ var1output_success|raw }}' => "{% include 'report/policy/success.html.twig' with {'result': response } %}",

      ]);
    }

    const MUSTACHE_OPEN = '{{';
    const MUSTANCE_CLOSE = '}}';

    protected function convertMustache2TwigSyntax($template)
    {
      $template = strtr($template, [
        '{{{' => '{{',
        '}}}' => '|raw }}'
      ]);
      if (strpos($template, self::MUSTACHE_OPEN) === FALSE) {
        return $template;
      }
      $control_structure = [];
      $tokens = [];
      while (($pos = strpos($template, self::MUSTACHE_OPEN)) !== FALSE) {
        $end_pos = strpos($template, self::MUSTANCE_CLOSE);

        $mustache_statement = substr($template, $pos, $end_pos - $pos + strlen(self::MUSTANCE_CLOSE));
        $syntax = trim(strtr($mustache_statement, [
          self::MUSTACHE_OPEN => '',
          self::MUSTANCE_CLOSE => '',
        ]));

        switch (substr($syntax, 0, 1)) {
          case '^':
            $twig_statement = "{% if not " . trim(substr($syntax, 1)) . " %}";
            $control_structure[] = 'endif';
            break;
          case '#':
            // Look for known variables used as if statements.
            if (in_array(trim(substr($syntax, 1)), ['passes', 'notices', 'failures', 'warnings', 'errors'])) {
              $twig_statement = "{% if __" . trim(substr($syntax, 1)) . " %}";
              $control_structure[] = 'endif';
              break;
            }
            // Otherwise assume its a foreach.
            $variable = 'var' . count($control_structure).trim(substr($syntax, 1));
            $twig_statement = "{% for $variable in " . trim(substr($syntax, 1)) . " %}";
            $control_structure[] = 'endfor';
            break;
          case '/':
            $twig_statement = "{% " . array_pop($control_structure) . " %}";
            break;
          case '.':
            $twig_statement = "{{ " . $variable . substr($syntax, 1) .  " }}";
            break;
          default:
            $twig_statement = "{{ " . $syntax . " }}";
        }
        $token_name = hash('md5', $twig_statement . count($tokens));
        $tokens[$token_name] = $twig_statement;

        $template = substr($template, 0, $pos).$token_name.substr($template, $end_pos + strlen(self::MUSTANCE_CLOSE));
      }
      return strtr($template, $tokens);
    }
}
