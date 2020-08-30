<?php

namespace Drutiny\Report;

use Drutiny\AssessmentInterface;
use Drutiny\Profile;
use Drutiny\Console\Verbosity;
use Drutiny\AuditResponse\AuditResponse;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\TwigFunction;

abstract class Format implements FormatInterface
{

    protected $format = 'unknown';
    protected $extension = 'txt';
    protected $output;
    protected $buffer;
    protected $twig;
    protected $options = [];
    protected $logger;

    public function __construct(Verbosity $verbosity, Environment $twig, LoggerInterface $logger)
    {
        $this->output = new BufferedOutput($verbosity->get(), true);
        $this->buffer = new BufferedOutput($verbosity->get(), true);
        $this->twig = $twig;
        $this->twig->addGlobal('ext', $this->getExtension());
        //$this->twig->addGlobal('logger', $logger);
        $this->logger = $logger;
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
        ]);
    }

    public static function keyed($variable) {
      return is_array($variable) && is_string(reset($variable));
    }

    public function render(Profile $profile, AssessmentInterface $assessment)
    {
        try {
          $template = $this->options['template'];
          // 2.x backwards compatibility.
          if (strpos($template, '.twig') === false) {
            $this->logger->warning("Deprecated template declaration found: $template. Templates should explicitly specify extension (e.g. .md.twig).");
            $template .= '.'.$this->extension.'.twig';
          }
          $this->logger->debug("Rendering format with template $template.");
          $this->buffer->write($this->twig->render($template, [
            'profile' => $profile,
            'assessment' => $assessment,
            'sections' => $this->prepareContent($profile, $assessment),
          ]));
        }
        catch (\Twig\Error\Error $e) {
          $message[] = $e->getMessage();
          foreach ($e->getTrace() as $stack) {
            $message[] = strtr('file:line', $stack);
          }
          $this->logger->error(implode("\n", $message));
          if ($source = $e->getSourceContext()) {
            $this->logger->info($source->getCode());
          }
          throw $e;
        }
        return $this;
    }

    /**
     * Attempt to load a twig template based on the provided format extension.
     *
     * Each class implementing this one will provide a property called "extension".
     * This extension becomes apart of a prefix used to auto load twig templates.
     *
     * @param $name the namespace for the template. E.g. "content/page"
     */
    final public function loadTwigTemplate($name)
    {
      return $this->twig->load(sprintf('%s.%s.twig', $name, $this->extension));
    }

    abstract protected function prepareContent(Profile $profile, AssessmentInterface $assessment);

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

    public function getOutput():OutputInterface
    {
        return $this->output;
    }

    final public function setOutput(OutputInterface $output)
    {
      $this->output = $output;
    }

    final public function write()
    {
      $this->output->write($this->buffer->fetch());
    }

    public function getName():string
    {
        return $this->format;
    }
}
