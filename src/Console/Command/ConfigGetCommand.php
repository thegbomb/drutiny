<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Command\Command;

/**
 * A console command for autowiring information.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 *
 * @internal
 */
class ConfigGetCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Lists configuration')
            ->setHelp('List all the configuraiton inside Drutiny')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorIo = $io->getErrorStyle();

        $builder = $this->getApplication()->getKernel()->getContainer();

        $io->title('Configuration');
        $io->text('The following configuration can be customised:');

        $config = $builder->getParameterBag()->all();
        ksort($config);

        $rows = [];
        foreach ($config as $key => $value) {
          $rows[] = [$key, Yaml::dump($value)];
        }

        $io->table(['Key', 'Value (YAML formatted)'], $rows);

        return 0;
    }

    /**
     * @internal
     */
    public function filterToServiceTypes(string $serviceId): bool
    {
        // filter out things that could not be valid class names
        if (!preg_match('/(?(DEFINE)(?<V>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+))^(?&V)(?:\\\\(?&V))*+(?: \$(?&V))?$/', $serviceId)) {
            return false;
        }

        // if the id has a \, assume it is a class
        if (false !== strpos($serviceId, '\\')) {
            return true;
        }

        return class_exists($serviceId) || interface_exists($serviceId, false);
    }

    public static function getClassDescription(string $class, string &$resolvedClass = null): string
    {
        $resolvedClass = $class;
        try {
            $resource = new ClassExistenceResource($class, false);

            // isFresh() will explode ONLY if a parent class/trait does not exist
            $resource->isFresh(0);

            $r = new \ReflectionClass($class);
            $resolvedClass = $r->name;

            if ($docComment = $r->getDocComment()) {
                $docComment = preg_split('#\n\s*\*\s*[\n@]#', substr($docComment, 3, -2), 2)[0];

                return trim(preg_replace('#\s*\n\s*\*\s*#', ' ', $docComment));
            }
        } catch (\ReflectionException $e) {
        }

        return '';
    }
}
