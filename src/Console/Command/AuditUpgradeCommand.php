<?php

namespace Drutiny\Console\Command;

use Drutiny\Upgrade\AuditUpgrade;
use Fiasco\SymfonyConsoleStyleMarkdown\Renderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 *
 */
class AuditUpgradeCommand extends Command
{

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('audit:upgrade')
        ->setDescription('Show all php audit classes available.')
        ->addArgument(
            'audit',
            InputArgument::REQUIRED,
            'The PHP class including the namespace.'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $reflection = new \ReflectionClass($input->getArgument('audit'));
        $helper = new AuditUpgrade($reflection);


        $contents = file($reflection->getFileName());
        $replacements = [
          'use Drutiny\Annotation\Param;'.PHP_EOL => '',
          'use Drutiny\Annotation\Token;'.PHP_EOL => '',
          'use Drutiny\Driver\DrushFormatException;'.PHP_EOL => '',
          '\Drutiny\Driver\DrushFormatException $e' => '\Exception $e',
          'Drutiny\Driver\DrushFormatException $e' => '\Exception $e',
          'DrushFormatException $e' => '\Exception $e',
          '$sandbox->setParameter' => '$this->set',
          '$sandbox->getParameter' => '$this->getParameter',
        //  'getParameterTokens()' => 'get("parameters")->all()'
        ];

        $replacements = array_merge($replacements, $helper->getParamAnnotationReplacements());

        $configure_code = [];
        foreach ($helper->getParamAnnotations() as $param) {
          $configure_code[] = $helper->getParameterDeclaration($param['name'], $param['description'], 'static::PARAMETER_OPTIONAL', $param['default'] ?? '');
        }

        $new_code = implode('', $configure_code);

        $method = $reflection->getMethod('configure');
        if ($method->getDeclaringClass() == $reflection) {
          $code = [];
          for ($i = $method->getStartLine(); $i < $method->getEndLine(); $i++) {
            $code[] = $contents[$i];
          }
          array_pop($code);
          $code = implode('', $code);
          $replacements[$code] = $code . $new_code;
        }
        elseif (!empty($new_code)) {
          $new_code = "public function configure()\n    {\n   $new_code\n    }\n\n";
          $contents[$reflection->getStartLine()] .= "\n    $new_code";
        }
        $contents = implode('', $contents);

        file_put_contents($reflection->getFileName(), strtr($contents, $replacements));

        $output->writeln("Re-written ".$reflection->getFileName());

        if (file_exists('./vendor/bin/phpcbf')) {
          $output->writeln("Running PHPCBF on ".$reflection->getFileName());
          passthru('./vendor/bin/phpcbf ' . $reflection->getFileName());
        }

        return 1;
    }
}
