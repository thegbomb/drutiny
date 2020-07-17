<?php

namespace Drutiny\Console\Command;

use Drutiny\PolicyFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


/**
 *
 */
class AuditInfoCommand extends Command
{

    protected $policyFactory;

    public function __construct(PolicyFactory $policyFactory)
    {
        $this->policyFactory = $policyFactory;
        parent::__construct();
    }

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('audit:info')
        ->setDescription('Show all php audit classes available.')
        ->addArgument(
            'audit',
            InputArgument::REQUIRED,
            'The name of the audit class to display info about.'
        );
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $audit = $input->getArgument('audit');
        $reflection = new \ReflectionClass($audit);
        $info = [];

        $info[] = ['Namespace', $audit];
        $info[] = ['Extends', $reflection->getParentClass()->name];

        $policy_list = array_filter($this->policyFactory->getPolicyList(), function ($policy) use ($audit) {
            return $policy['class'] == $audit;
        });

        $info[] = ['Policies', $this->listing(array_map(function ($policy) {
          return $policy['name'];
        }, $policy_list))];

        $info[] = ['Filename', $reflection->getFilename()];
        $info[] = ['Methods', $this->listing(array_map(function ($method) use ($audit) {
          if ($method->getDeclaringClass()->name !== $audit) {
            return false;
          }
          $function = $method->name . '(';
          foreach ($method->getParameters() as $parameter) {
            $function .= '$' . $parameter->name . ', ';
          }
          $function = substr($function, 0, -2) . ')';

          return $function;
        }, $reflection->getMethods()))];


        $io = new SymfonyStyle($input, $output);
        $io->title('Audit Info');
        $io->table([], $info);
        return 0;
    }


    protected function listing($array) {
      return  "* ".implode("\n* ", array_filter($array));
    }
}
