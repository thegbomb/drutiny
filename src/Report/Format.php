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

    /**
     * Attempt to load a twig template based on the provided format extension.
     *
     * Each class implementing this one will provide a property called "extension".
     * This extension becomes apart of a prefix used to auto load twig templates.
     *
     * @param $name the namespace for the template. E.g. "content/page"
     */
    protected function loadTwigTemplate($name)
    {
      return $this->twig->load(sprintf('%s.%s.twig', $name, $this->extension));
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

    protected static function mapDrutiny2toDrutiny3variables($template)
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

        'var0output_error in output_error' => "response in assessment.results|filter(r => r.hasError)",
        '{{ var0output_error|raw }}' => "{% include 'report/policy/error.html.twig' with {'result': response } %}",

        'var1output_error in output_error' => "response in assessment.results|filter(r => r.hasError)",
        '{{ var1output_error|raw }}' => "{% include 'report/policy/error.html.twig' with {'result': response } %}",


        'var1output_success in output_success' => "response in assessment.results|filter(r => r.isSuccessful)",
        '{{ var1output_success|raw }}' => "{% include 'report/policy/success.html.twig' with {'result': response } %}",

      ]);
    }

    public static function convertMustache2TwigSyntax($sample) {
      $tokens = [];
      while (preg_match_all('/({?{{)\s*([\#\^\/ ])?\s*([a-zA-Z0-9]+|\.)\s?(}}}?)/', $sample, $matches)) {
        foreach ($matches[3] as $idx => $variable) {
          $operator = $matches[2][$idx];
          $is_raw = (strlen($matches[1][$idx]) == 3);
          $syntax = $matches[0][$idx];

          // If condition or loop.
          if ($operator == '#') {
            // Find closing condition.
            $closing_idx = array_search($variable, array_slice($matches[3], $idx+1, null, true));
            if ($matches[2][$closing_idx] != '/') {
              throw new Exception("Expected closing statement for $variable. Found: {$matches[0][$closing_idx]}");
            }
            $start = strpos($sample, $matches[0][$idx]);
            $end = strpos(substr($sample, $start), $matches[0][$closing_idx]) + strlen($matches[0][$closing_idx]);
            $snippet = substr($sample, $start, $end);
            $token = md5($snippet);

            $content = substr($sample, $start + strlen($syntax), $end - strlen($syntax) - strlen($matches[0][$closing_idx]));
            $tokens[$token] = static::mustache_tpl($variable, static::convertMustache2TwigSyntax($content));

            $sample = implode($token, explode($snippet, $sample, 2));
            break;
          }
          elseif (empty($operator)) {
            $token = md5($syntax);
            $var = '{{'.($variable == '.' ? 'self' : $variable).($is_raw ? '|raw':'').'}}';
            $tokens[$token] = $var;
            $sample = implode($token, explode($syntax, $sample, 2));
            break;
          }
          throw new Exception("Unknown condition met: $operator $variable.");
        }
      }
      return strtr($sample, $tokens);
    }

    private static function mustache_tpl($variable, $content) {
      return <<<HTML
      {% if $variable is iterable %}
          {% for self in $variable %}{% if self is iterable %}{% with self %}$content{% endwith %}{% else %}$content{% endif %}{% endfor %}
      {% else %}{% endif %}
      HTML;
    }
}
