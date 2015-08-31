<?php

namespace Kaliop\QueueingBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Kaliop\QueueingBundle\Helper\BaseCommand;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Sends to a queue a message specified as json
 */
class QueueGenericMessageCommand extends BaseCommand
{
    /// @var \Symfony\Component\Console\Output\OutputInterface $output
    protected $output;

    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:queuemessage')
            ->setDescription("Sends to a queue a pre-formatted message")
            ->addArgument('queue_name', InputArgument::REQUIRED, 'The queue name (string)')
            ->addArgument('message', InputArgument::REQUIRED, 'The message body (string)')
            ->addOption('driver', 'b', InputOption::VALUE_OPTIONAL, 'The driver (string), if not default', null)
            ->addOption('routing-key', 'k', InputOption::VALUE_OPTIONAL, 'The routing key, if needed (string)', null)
            ->addOption('content-type', 'c', InputOption::VALUE_OPTIONAL, 'The message body content-type, defaults to application/json (string)', null)
            ->addOption('repeat', 'r', InputOption::VALUE_OPTIONAL, 'The number of times to send the message, 1 by default (int)', 1)
            ->addOption('ttl', 't', InputOption::VALUE_OPTIONAL, 'Validity of message (in seconds)', null)
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

        /// @todo move into the driver
        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool)$input->getOption('debug'));
        }

        $driverName = $input->getOption('driver');
        $queue = $input->getArgument('queue_name');
        $message = $input->getArgument('message');
        $contentType = $input->getOption('content-type');
        $key = $input->getOption('routing-key');
        $repeat = $input->getOption('repeat');
        $ttl = $input->getOption('ttl');

        $driver = $this->getContainer()->get('kaliop_queueing.driverManager')->getDriver($driverName);
        $messageProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $messageProducer->setDriver($driver);
        $messageProducer->setQueueName($queue);
        try {
            for ($i = 0; $i < $repeat; $i++)
                $messageProducer->publish(
                    $message,
                    $contentType,
                    $key,
                    $ttl
                );

            $this->writeln("$repeat message(s) queued" . ($ttl ? ", will be valid for $ttl seconds" : ''));
        } catch (ServiceNotFoundException $e) {
            throw new \InvalidArgumentException("Queue '$queue' is not registered");
        }

    }

}