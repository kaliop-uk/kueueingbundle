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
            ->addArgument('message_body', InputArgument::REQUIRED, 'The message body (string)')
            ->addOption('driver', 'i', InputOption::VALUE_REQUIRED, 'The driver (string), if not default', null)
            ->addOption('routing-key', 'r', InputOption::VALUE_REQUIRED, 'The routing key, if needed (string)', null)
            ->addOption('content-type', 'c', InputOption::VALUE_REQUIRED, 'The message body content-type, defaults to application/json (string)', null)
            ->addOption('messages', 'm', InputOption::VALUE_REQUIRED, 'The number of times to send the message, 1 by default (int)', 1)
            ->addOption('batch', 'b', InputOption::VALUE_NONE, 'Use Batch API for sending (depends on driver)')
            ->addOption('ttl', 't', InputOption::VALUE_OPTIONAL, 'Validity of message (in seconds)', null)
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging')
        ;
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

        $driverName = $input->getOption('driver');
        $queue = $input->getArgument('queue_name');
        $message = $input->getArgument('message_body');
        $contentType = $input->getOption('content-type');
        $key = $input->getOption('routing-key');
        $repeat = $input->getOption('messages');
        $ttl = $input->getOption('ttl');
        $debug = $input->getOption('debug');
        $batchMode = $input->getOption('batch');

        $driver = $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        if ($debug !== null) {
            $driver->setDebug($debug);
        }
        $messageProducer = $this->getContainer()->get('kaliop_queueing.message_producer.generic_message');
        $messageProducer->setDriver($driver);
        $messageProducer->setQueueName($queue);

        try {
            if ($batchMode) {
                $messageProducer->batchPublish(array_fill(0, $repeat, $message), $contentType, $key, $ttl);
            } else {
                for ($i = 0; $i < $repeat; $i++) {
                    $messageProducer->publish($message, $contentType, $key, $ttl);
                }
            }

            $this->writeln("$repeat message(s) queued" . ($ttl ? ", will be valid for $ttl seconds" : ''));
        } catch (ServiceNotFoundException $e) {
            throw new \InvalidArgumentException("Queue '$queue' is not registered");
        }
    }

}