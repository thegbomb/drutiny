<?php

namespace Drutiny\Report\Twig;

use Drutiny\AssessmentInterface;
use Drutiny\AuditResponse\AuditResponse;
use Twig\Environment;


class Helper {
  /**
   * Registered as a Twig filter to be used as: "Title here"|heading.
   */
  public static function filterSectionHeading(Environment $env, $heading, $level = 2)
  {
    return $env
      ->createTemplate('<h'.$level.' class="section-title" id="section_{{ heading | u.snake }}">{{ heading }}</h'.$level.'>')
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

  public static function renderAuditReponse(Environment $twig, AuditResponse $response, AssessmentInterface $assessment)
  {
      $globals = $twig->getGlobals();
      $template = 'report/policy/'.$response->getType().'.'.$globals['ext'].'.twig';
      $globals['logger']->info("Rendering audit response for ".$response->getPolicy()->name.' with '.$template);
      $globals['logger']->info('Keys: ' . implode(', ', array_keys($response->getTokens())));
      return $twig->render($template, [
        'audit_response' => $response,
        'assessment' => $assessment,
        'target' => $assessment->getTarget(),
      ]);
  }

  public static function keyed($variable) {
    return is_array($variable) && is_string(key($variable));
  }

  public static function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
      $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }

}

 ?>
