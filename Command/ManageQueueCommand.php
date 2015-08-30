<?php
/**
 * User: gaetano.giunta
 * Date: 19/05/14
 * Time: 18.29
 */

namespace Kaliop\QueueingBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Kaliop\QueueingBundle\Helper\BaseCommand;

class ManageQueueCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:managequeue')
            ->setDescription("Sends control commands to a queue to f.e. purge it or grab some stats")
            ->addArgument('action', InputArgument::REQUIRED, 'The action to execute. use "help" to see all available')
            ->addArgument('queue_name', InputArgument::OPTIONAL, 'The queue name (string)', '')
            ->addOption('driver_name', 'b', InputOption::VALUE_OPTIONAL, 'The driver (string), if not default', null)
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

        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool)$input->getOption('debug'));
        }

        $driverName = $input->getOption('driver_name');
        $command = $input->getArgument('action');
        $queue = $input->getArgument('queue_name');

        $driver = $this->getContainer()->get('kaliop_queueing.driverManager')->getDriver($driverName);
        $queueManager = $driver->getQueueManager($queue);

        if ($command == 'help') {
            $this->writeln("Available actions: " . implode(', ', $queueManager->listActions($queue)));
            return;
        }

        if (!in_array($command, $queueManager->listActions($queue))) {
            $this->writeln("Unrecognized action $command\nAvailable actions: " . implode(', ', $queueManager->listActions($queue)));
            return;
        }

        $queueManager->setQueueName($queue);
        $result = $queueManager->executeAction($command);

        $this->writeln("Sent '$command' to queue $queue");

        if ($result != '') {
            if (is_array($result)) {
                $result = print_r($result, true);
            }
            $this->writeln("Result: $result");
        }
    }
}