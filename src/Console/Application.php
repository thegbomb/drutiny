<?php

namespace Drutiny\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drutiny\Kernel;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Application extends BaseApplication
{
    private $kernel;
    private $commandsRegistered = false;
    private $registrationErrors = [];

    public function __construct(Kernel $kernel, $version)
    {
        $this->kernel = $kernel;
        parent::__construct('Drutiny', $version);
    }

    /**
     * Gets the Kernel associated with this Console.
     *
     * @return KernelInterface A KernelInterface instance
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        if ($this->kernel->getContainer()->has('services_resetter')) {
            $this->kernel->getContainer()->get('services_resetter')->reset();
        }
    }

    /**
     * Runs the current application.
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->registerCommands();

        if ($this->registrationErrors) {
            $this->renderRegistrationErrors($input, $output);
        }

        //$this->setDispatcher($this->kernel->getContainer()->get('event_dispatcher'));

        return parent::doRun($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if (!$command instanceof ListCommand) {
            if ($this->registrationErrors) {
                $this->renderRegistrationErrors($input, $output);
                $this->registrationErrors = [];
            }

            return parent::doRunCommand($command, $input, $output);
        }

        $returnCode = parent::doRunCommand($command, $input, $output);

        if ($this->registrationErrors) {
            $this->renderRegistrationErrors($input, $output);
            $this->registrationErrors = [];
        }

        return $returnCode;
    }

    /**
     * {@inheritdoc}
     */
    public function find($name)
    {
        $this->registerCommands();

        return parent::find($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        $this->registerCommands();

        $command = parent::get($name);

        if ($command instanceof ContainerAwareInterface) {
            $command->setContainer($this->kernel->getContainer());
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function all($namespace = null)
    {
        $this->registerCommands();

        return parent::all($namespace);
    }

    public function add(Command $command)
    {
        $this->registerCommands();

        return parent::add($command);
    }

    protected function registerCommands()
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $container = $this->kernel->getContainer();
        $container->findTags();
        foreach ($container->findTaggedServiceIds('command') as $id => $definition) {
          $this->add($container->get($id));
        }
    }

    private function renderRegistrationErrors(InputInterface $input, OutputInterface $output)
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        (new SymfonyStyle($input, $output))->warning('Some commands could not be registered:');

        foreach ($this->registrationErrors as $error) {
            $this->doRenderThrowable($error, $output);
        }
    }
}
