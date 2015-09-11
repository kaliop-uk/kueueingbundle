<?php

namespace Kaliop\QueueingBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Kaliop\QueueingBundle\Helper\BaseCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Sends to a queue a message to execute a symfony console command
 */
class QueueConsoleCommandCommand extends BaseCommand
{
    /// @var \Symfony\Component\Console\Output\OutputInterface $output
    protected $output;

    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:queuecommand')
            ->setDescription("Sends to a queue a message to execute a symfony console command")
            ->addArgument('queue_name', InputArgument::REQUIRED, 'The queue name (string)')
            ->addArgument('console_command', InputArgument::REQUIRED, 'The console command to execute (string)')
            ->addArgument('arguments', InputArgument::IS_ARRAY, 'Arguments for the executed command')
            ->addOption('option', 'o', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Options for the executed command; use the syntax: name=value', array())
            ->addOption('driver', 'i', InputOption::VALUE_OPTIONAL, 'The driver (string), if not default', null)
            ->addOption('routing-key', 'r', InputOption::VALUE_REQUIRED, 'The routing key, if needed (string)', null)
            ->addOption('ttl', 't', InputOption::VALUE_REQUIRED, 'Validity of message (in seconds)', null)
            ->addOption('novalidate', null, InputOption::VALUE_NONE, 'Skip checking if the command is registered with the sf console')
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);

        $command = $input->getArgument('console_command');
        if (!$input->getOption('novalidate')) {
            if (!in_array($command, array_keys($this->getApplication()->all()))) {
                throw new \InvalidArgumentException("Command '$command' is not registered in the symfony console");
            }
        }

        $driverName = $input->getOption('driver');
        $queue = $input->getArgument('queue_name');
        $key = $input->getOption('routing-key');
        $ttl = $ttl = $input->getOption('ttl');
        $debug = $input->getOption('debug');
        $arguments = $input->getArgument('arguments');
        $cliOptions = $input->getOption('option');

        // parse arguments to tell options apart
        $options = array();
        foreach ($cliOptions as $key => $arg) {
            $arg = explode('=', $arg, 2);
            $options[$arg[0]] = ((count($arg) == 2) ? $arg[1] : null);
        }

        $driver = $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        if ($debug !== null) {
            $driver->setDebug($debug);
        }
        $messageProducer = $this->getContainer()->get('kaliop_queueing.message_producer.console_command');
        $messageProducer->setDriver($driver);
        $messageProducer->setQueueName($queue);
        try {
            $messageProducer->publish(
                $command,
                $arguments,
                $options,
                $key,
                $ttl
            );

            $this->writeln("Command queued for execution" . ($ttl ? ", will be valid for $ttl seconds" : ''));
        } catch (ServiceNotFoundException $e) {
            throw new \InvalidArgumentException("Queue '$queue' is not registered");
        }

    }

}