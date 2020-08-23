<?php

namespace Drutiny\Report;

use Drutiny\Report\Backport\MustacheParser;

Trait BackportTemplateHacks
{
    protected static function prefixTemplate($template)
    {
      return '
      {%- if assessment %}
      {# Deprecated variables supporting 2.x #}
      {% set errors = assessment.results|filter(r => r.hasError)|length %}
      {% set failures = assessment.results|filter(r => r.isFailure)|length %}
      {% set notices = assessment.results|filter(r => r.isNotice)|length %}
      {% set warnings = assessment.results|filter(r => r.hasWarning)|length %}
      {% set passes = assessment.results|filter(r => r.isSuccessful)|length %}
      {% endif -%}
      '.$template;
    }

    protected static function preMapDrutiny2Variables($template)
    {
      $variables = [
        'output_failure' => "{% for result in assessment.results|filter(r => r.isFailure) %}{{ policy_result(result, assessment) }}{% endfor %}",
        'output_warning' => "{% for result in assessment.results|filter(r => r.hasWarning) %}{{ policy_result(result, assessment) }}{% endfor %}",
        'output_error'   => "{% for result in assessment.results|filter(r => r.hasError) %}{{ policy_result(result, assessment) }}{% endfor %}",
        'output_notice'  => "{% for result in assessment.results|filter(r => r.isNotice) %}{{ policy_result(result, assessment) }}{% endfor %}",
        'output_success' => "{% for result in assessment.results|filter(r => r.isSuccessful) %}{{ policy_result(result, assessment) }}{% endfor %}",
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
        '{{{ appendix_table }}}' => "{% include 'report/page/appendix_table.html.twig' %}",
        '{{{appendix_table}}}' => "{% include 'report/page/appendix_table.html.twig' %}",
        '{{appendix_table|raw}}' => "{% include 'report/page/appendix_table.html.twig' %}",
        '{{severity_stats|raw}}' => "{% include 'report/page/severity_stats.html.twig' %}",
        '{{{ severity_stats }}}' => "{% include 'report/page/severity_stats.html.twig' %}",
        '{{summary_table|raw}}' => "{% include 'report/page/summary_table.html.twig' %}",
        '{{{ summary_table }}}' => "{% include 'report/page/summary_table.html.twig' %}",

        '{% for var0remediations in remediations %}' => "{% for response in assessment.results|filter(r => r.isFailure) %}",
        '{{ var0remediations |raw }}' => "{% with response.tokens %}{{ include(template_from_string(response.policy.remediation)) | markdown_to_html }}{% endwith %}",

        'var1output_failure in output_failure' => "response in assessment.results|filter(r => r.isFailure)",
        '{{ var1output_failure|raw }}' => "{{ policy_result(response) }}",

        'var1output_warning in output_warning' => "response in assessment.results|filter(r => r.hasWarning)",
        '{{ var1output_warning|raw }}' => "{{ policy_result(response) }}",

        'var1output_notice in output_notice' => "response in assessment.results|filter(r => r.isNotice)",
        '{{ var1output_notice|raw }}' => "{{ policy_result(response) }}",

        'var0output_error in output_error' => "response in assessment.results|filter(r => r.hasError)",
        '{{ var0output_error|raw }}' => "{{ policy_result(response) }}",

        'var1output_error in output_error' => "response in assessment.results|filter(r => r.hasError)",
        '{{ var1output_error|raw }}' => "{{ policy_result(response) }}",

        'var1output_success in output_success' => "response in assessment.results|filter(r => r.isSuccessful)",
        '{{ var1output_success|raw }}' => "{{ policy_result(response) }}",

        "{{_uri}}" => "{{assessment.uri}}",
      ]);
    }

    public static function convertMustache2TwigSyntax($sample) {
      // Convert old chart syntax to new syntax.
      // Old (2.x): {{{_chart.foo}}}
      // New (3.x): {{chart.foo|chart}}
      $sample = preg_replace("/{{{_chart.(.+)}}}/", '{{audit_response.policy.chart.$1|chart}}', $sample);
      $sample = self::mapDrutiny2toDrutiny3variables($sample);
      return MustacheParser::reformat($sample);
    }

    public static function mustache($variable):iterable
    {
      return is_iterable($variable) ? $variable : [$variable];
    }
}
