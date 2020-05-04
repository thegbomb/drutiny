<?php

namespace Drutiny\Report;

use Drutiny\Assessment;
use Drutiny\Profile;
use Drutiny\Target\Target;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class Format implements FormatInterface {

  /**
   * The format the object applies to.
   *
   * @var string
   */
  protected $format = 'unknown';

  /**
   * The output object.
   *
   * @var Symfony\Component\Console\Output\BufferedOutput
   */
  protected $output;

  public function __construct()
  {
    $this->output = new BufferedOutput(drutiny()->get('verbosity')->get(), TRUE);
  }

  public function setOptions(array $options = [])
  {

  }

  public static function create($format, $options = [])
  {
    $formats = Config::get('Format');
    if (!isset($formats[$format])) {
      throw new \InvalidArgumentException("Reporting format '$format' is not supported.");
    }
    return new $formats[$format]($options);
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

  final public function render(Profile $profile, Target $target, array $results)
  {
    $filepaths = [];

    if (count($results) == 1) {
      $result = reset($results);
      // @var array
      $variables = $this->preprocessResult($profile, $target, $result);

      // @var string
      $renderedOutput = $this->renderResult($variables);
    }
    else {

      // @var array
      $variables = $this->preprocessMultiResult($profile, $target, $results);

      // @var string
      $renderedOutput = $this->renderMultiResult($variables);
    }

    $this->getOutput()->write($renderedOutput, FALSE);
    return $this->getOutput();
  }

  abstract protected function preprocessResult(Profile $profile, Target $target, Assessment $assessment);
  abstract protected function preprocessMultiResult(Profile $profile, Target $target, array $results);

  abstract protected function renderResult(array $variables);
  abstract protected function renderMultiResult(array $variables);

  /**
   * Render an HTML template.
   *
   * @param string $tpl
   *   The name of the .html.tpl template file to load for rendering.
   * @param array $render
   *   An array of variables to be used within the template by the rendering engine.
   *
   * @return string
   */
  public function renderTemplate($tpl, array $render) {
    $registry = new \Drutiny\Registry();
    $loader = new \Twig_Loader_Filesystem($registry->templateDirs());
    $twig = new \Twig_Environment($loader, array(
      'cache' => sys_get_temp_dir() . '/drutiny/cache',
      'auto_reload' => TRUE,
      // 'debug' => true,
    ));
    // $twig->addExtension(new \Twig_Extension_Debug());

    // Filter to sort arrays by a property.
    $twig->addFilter(new \Twig_SimpleFilter('psort', function (array $array, array $args = []) {
        $property = reset($args);

        usort($array, function ($a, $b) use ($property) {
          if ($a[$property] == $b[$property]) {
            return 0;
          }
          $index = [$a[$property], $b[$property]];
          sort($index);
          return $index[0] == $a[$property] ? 1 : -1;
        });

        return $array;
    },
    ['is_variadic' => true]));

    $template = $twig->load($tpl . '.' . $this->getFormat() . '.twig');
    $contents = $template->render($render);
    return $contents;
  }

  /**
   * Get the profile title.
   */
  public function getOutput()
  {
    return $this->output;
  }

  public function getName() {
    return $this->format;
  }
}

 ?>
