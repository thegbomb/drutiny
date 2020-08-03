<?php

namespace Drutiny\Report;

Trait BackportTemplateHacks
{
    protected static function prefixTemplate($template)
    {
      return '
      {% if assessment %}
      {# Deprecated variables supporting 2.x #}
      {% set errors = assessment.results|filter(r => r.hasError)|length %}
      {% set failures = assessment.results|filter(r => r.isFailure)|length %}
      {% set notices = assessment.results|filter(r => r.isNotice)|length %}
      {% set warnings = assessment.results|filter(r => r.hasWarning)|length %}
      {% set passes = assessment.results|filter(r => r.isSuccessful)|length %}
      {% endif %}
      '.$template;
    }

    protected static function preMapDrutiny2Variables($template)
    {
      $variables = [
        'output_failure' => "{% for result in assessment.results|filter(r => r.isFailure) %}{% include 'report/policy/failure.html.twig' %}{% endfor %}",
        'output_warning' => "{% for result in assessment.results|filter(r => r.hasWarning) %}{% include 'report/policy/warning.html.twig' %}{% endfor %}",
        'output_error'   => "{% for result in assessment.results|filter(r => r.hasError) %}{% include 'report/policy/error.html.twig' %}{% endfor %}",
        'output_notice'  => "{% for result in assessment.results|filter(r => r.isNotice) %}{% include 'report/policy/notice.html.twig' %}{% endfor %}",
        'output_success' => "{% for result in assessment.results|filter(r => r.isSuccessful) %}{% include 'report/policy/success.html.twig' %}{% endfor %}",
      ];
      foreach ($variables as $variable => $twig) {
          $template = preg_replace_callback("/{{# ?$variable ?}}.*{{\/ ?$variable ?}}/s", function ($matches) use ($twig) {
              return $twig;
          }, $template);
      }

      // Handle remediations variable.
      $template = preg_replace_callback("/{{# ?remediations ?}}(.*)({{{ ?\. ?}}})(.*){{\/ ?remediations ?}}/s", function ($matches) {
          return '{% for response in assessment.results|filter(r => r.isFailure) %}
            {% with response.tokens %}
              '.$matches[1].'{{ include(template_from_string(response.policy.remediation | mustache2twig)) | markdown_to_html }}'.$matches[3].'
            {% endwith %}
          {% endfor %}';
      }, $template);

      return $template;
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
        '{{appendix_table|raw}}' => "{% include 'report/page/appendix_table.html.twig' %}",
        '{{severity_stats|raw}}' => "{% include 'report/page/severity_stats.html.twig' %}",
        '{{summary_table|raw}}' => "{% include 'report/page/summary_table.html.twig' %}",

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
      // Convert old chart syntax to new syntax.
      // Old (2.x): {{{_chart.foo}}}
      // New (3.x): {{chart.foo|chart}}
      $sample = preg_replace("/{{{_chart.(.+)}}}/", '{{result.policy.chart.$1|chart}}', $sample);

      $tokens = [];
      while (preg_match_all('/({?{{)\s*([\#\^\/ ])?\s*([a-zA-Z0-9_\.]+|\.)\s?(}}}?)/', $sample, $matches)) {
        foreach ($matches[3] as $idx => $variable) {
          $operator = $matches[2][$idx];
          $is_raw = (strlen($matches[1][$idx]) == 3);
          $syntax = $matches[0][$idx];

          // If condition or loop.
          if (in_array($operator, ['#', '^'])) {
            // Find closing condition.
            $i = $idx+1;
            do {
                $slice = array_slice($matches[3], $i, null, true);

                if (empty($slice)) {
                  throw new \Exception("Expected closing statement for $variable in \n $sample");
                }
                $closing_idx = array_search($variable, $slice);

                if ($matches[2][$closing_idx] != '/') {
                    $i++;
                    continue;
                }
                break;
            }
            while (true);

            $start = strpos($sample, $syntax);
            $end = strpos(substr($sample, $start), $matches[0][$closing_idx]) + strlen($matches[0][$closing_idx]);
            $snippet = substr($sample, $start, $end);
            $token = md5($snippet);

            $content = substr($sample, $start + strlen($syntax), $end - strlen($syntax) - strlen($matches[0][$closing_idx]));

            if ($operator == '#') {
              $tokens[$token] = static::mustache_tpl($variable, static::convertMustache2TwigSyntax($content));
            }
            elseif ($operator == '^') {
              $tokens[$token] = static::not_mustache_tpl($variable, static::convertMustache2TwigSyntax($content));
            }

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
          throw new \Exception("Unknown condition met: $operator $variable.");
        }
      }
      return strtr($sample, $tokens);
    }

    private static function not_mustache_tpl($variable, $content) {
      return <<<HTML
      {% if $variable is empty or not $variable %}$content{% endif %}
      HTML;
    }

    private static function mustache_tpl($variable, $content) {
      return <<<HTML
      {% if $variable is iterable %}
          {% for self in $variable %}{% if self is iterable %}{% with self %}$content{% endwith %}{% else %}$content{% endif %}{% endfor %}
      {% elseif $variable %}
        $content
      {% endif %}
      HTML;
    }
}
