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
            ->addArgument('argument/option', InputArgument::IS_ARRAY, 'Arguments and options for the executed command. Options use the syntax: option.<opt>.<val>')
            ->addOption('driver', 'b', InputOption::VALUE_OPTIONAL, 'The driver (string), if not default', null)
            ->addOption('routing-key', 'k', InputOption::VALUE_OPTIONAL, 'The routing key, if needed (string)', null)
            ->addOption('ttl', 't', InputOption::VALUE_OPTIONAL, 'Validity of message (in seconds)', null)
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

        /// @todo move into the driver
        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool)$input->getOption('debug'));
        }

        $driverName = $input->getOption('driver');
        $queue = $input->getArgument('queue_name');
        $key = $input->getOption('routing-key');
        $arguments = $input->getArgument('argument/option');
        // parse arguments to tell options apart
        $options = array();
        foreach ($arguments as $key => $arg) {
            if (strpos($arg, 'option.') === 0) {
                $arg = explode('.', $arg, 3);
                $options[$arg[1]] = ((count($arg) == 3) ? $arg[2] : null);
                unset($arguments[$key]);
            }
        }

        $driver = $this->getContainer()->get('kaliop_queueing.driverManager')->getDriver($driverName);
        $messageProducer = $this->getContainer()->get('kaliop_queueing.message_producer.console_command');
        $messageProducer->setDriver($driver);
        $messageProducer->setQueueName($queue);
        try {
            $messageProducer->publish(
                $command,
                $arguments,
                $options,
                $key,
                $ttl = $input->getOption('ttl')
            );

            $this->writeln("Command queued for execution" . ($ttl ? ", will be valid for $ttl seconds" : ''));
        } catch (ServiceNotFoundException $e) {
            throw new \InvalidArgumentException("Queue '$queue' is not registered");
        }

    }

}