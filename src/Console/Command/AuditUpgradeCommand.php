<?php

namespace Drutiny\Console\Command;

use Drutiny\Docs\AuditDocsGenerator;
use Drutiny\Registry;
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

        preg_match_all('/\* @Param\(([^\)]+)/m', $reflection->getDocComment(), $matches);
        $params = [];
        foreach($matches[1] as $blob) {
            $param = [];
            foreach (explode(PHP_EOL, $blob) as $line) {
                if (preg_match('/(name|type|default|description) = "(.*)",?/', $line, $result)) {
                    $param[$result[1]] = $result[2];
                }
            }
            $params[] = $param;
        }

        $contents = file($reflection->getFileName());
        $replacements = [
          'use Drutiny\Annotation\Param;'.PHP_EOL => '',
          'use Drutiny\Annotation\Token;'.PHP_EOL => '',
          '$sandbox->setParameter' => '$this->set',
          '$sandbox->getParameter' => '$this->getParameter'
        ];

        foreach ($matches[0] as $find) {
          $replacements[$find.')'.PHP_EOL.' '] = '';
        }

        $configure_code = [];
        foreach ($params as $param) {
          $configure_code[] = '      ->addParameter(';
          $configure_code[] = "          '{$param['name']}',";
          $configure_code[] = '          static::PARAMETER_OPTIONAL,';
          $configure_code[] = "          '{$param['description']}'".(isset($param['default']) ? ',' : '');
          if (isset($param['default'])) {
            $configure_code[] = "          {$param['default']}";
          }
          $configure_code[] = '      )';
        }

        $new_code = '';
        if (!empty($configure_code)) {
          $new_code = '      $this'.PHP_EOL.implode(PHP_EOL, $configure_code).';'.PHP_EOL;
        }

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
        else {
          $new_code = "public function configure()\n    {\n   $new_code\n    }\n";
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
