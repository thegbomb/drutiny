<?php

namespace Drutiny\Docs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drutiny\Registry;
use Drutiny\Profile\ProfileSource;
use Drutiny\PolicySource\PolicySource;
use Drutiny\Container;
use Drutiny\ExpressionFunction\DrutinyExpressionLanguageProvider;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Helper for building checks.
 */
class BuildDocsCommand extends Command
{

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('docs:build')
        ->setDescription('Build docs from source code annotations.');
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->clean()
         ->setup()
         ->buildPolicyLibrary($output)
         ->buildAuditLibrary($output)
         ->buildExpressionLanguageLibrary();
    }

    protected function buildAuditLibrary(OutputInterface $output)
    {
        $registry = new Registry();
        $finder = new Finder();
        $finder->files()
        ->in('.')
        ->path('/\/Audit\//')
        ->files()
        ->name('*.php');

        foreach ($finder as $file) {
            require_once $file->getPathname();
        }

        $auditors = array_filter(get_declared_classes(), function ($class) {
            return is_subclass_of($class, '\Drutiny\Audit');
        });

        $pages = [];
        foreach ($auditors as $class) {
            $docs = new AuditDocsGenerator();
            $namespace = explode('\\', $class);
            array_pop($namespace);
            $namespace = implode('\\', $namespace);
            $pages[$namespace][$class] = $docs->buildAuditDocumentation($class);
        }

        $nav = [];
        ksort($pages);
        foreach ($pages as $namespace => $list) {
            ksort($list);
            $filepath = 'audits/' . str_replace('\\', '', $namespace) . '.md';
            $nav_item = strtr($namespace, [
            'Drutiny\\' => '',
            'Audit\\' => '',
            'Audit' => '',
            '\\' => ' '
            ]);
            if (trim($nav_item) == '') {
                $nav_item = 'General';
            }
            $nav[] = [$nav_item => $filepath];
            $this->write($filepath, implode("\n\n", $list));
        }

        $mkdocs = Yaml::parse(file_get_contents('mkdocs.yml'));
        $mkdocs['pages'][4] = ['Audit Library' => $nav];
        $this->write('../mkdocs.yml', Yaml::dump($mkdocs, 6));

        return $this;
    }

    protected function buildPolicyLibrary(OutputInterface $output)
    {
        $source = PolicySource::getSource('localfs');
        $list = $source->getList();

        $toc = [];
        $pages = [];

        foreach ($list as $policy) {
            try {
                $policy = $source->load($policy);
                $docs = new PolicyDocsGenerator();
                $pages[$policy->get('name')] = $docs->buildPolicyDocumentation($policy);
            } catch (\ReflectionException $e) {
                Container::getLogger()->warning($e->getMessage());
            }
        }

        ksort($pages);
        $this->write('policy-library.md', implode("\n\n", $pages));
        return $this;
    }

    protected function buildExpressionLanguageLibrary()
    {
        $expressions = DrutinyExpressionLanguageProvider::registry();
        $reader = new AnnotationReader();

        $md = ['# Expression Language Syntax'];
        $md[] = '';
        $md[] = 'Drutiny uses Symfony\'s [Expression Language](http://symfony.com/doc/3.4/components/expression_language.html) ';
        $md[] = 'to enable Policies to provide depenency management and in some cases';
        $md[] = 'custom analysis (subject to if the Audit supports it).';
        $md[] = '';
        $md[] = 'The table below documents syntax Drutiny provides in addition to those';
        $md[] = 'provided by Symfony.';
        $md[] = '';
        $md[] = 'Function | Usage example | Description';
        $md[] = '-- | -- | --';

        foreach ($expressions as $class) {
            $reflection = new \ReflectionClass($class);
            $annotation = $reader->getClassAnnotation($reflection, 'Drutiny\Annotation\ExpressionSyntax');
            $md[] = implode(' | ', [$annotation->name, '`' . $annotation->usage . '`', $annotation->description]);
        }
        $this->write('explang-library.md', implode(PHP_EOL, $md));
    }

    protected function clean()
    {
        $paths = array_filter([
        'docs/policies',
        'docs/audits',
        'docs/api',
        'docs/img',
        'docs/index.md',
        'docs/explang-library.md'
        ], 'file_exists');

        foreach ($paths as $path) {
            exec('rm -rf ' . $path);
        }
        return $this;
    }

    protected function setup()
    {
        copy('README.md', 'docs/index.md');
        mkdir('docs/policies');
        mkdir('docs/audits');
        mkdir('docs/img');
        copy('assets/favicon.ico', 'docs/img/favicon.ico');
        return $this;
    }

    protected function findPackage($filepath)
    {
        $composer = false;
        while ($filepath) {
            $filepath = dirname($filepath);

            if (file_exists($filepath . '/composer.json')) {
                break;
            }
        }

        $json = file_get_contents($filepath . '/composer.json');
        $composer = json_decode($json, true);
        return $composer['name'];
    }

    protected function write($filename, $contents)
    {
        $filename = "docs/$filename";
        if (file_put_contents($filename, $contents)) {
            $length = strlen($contents);
            Container::getLogger()->info("Written $filename ($length)");
            return;
        }
        Container::getLogger()->error("Writting $filename failed.");
        return $this;
    }
}
