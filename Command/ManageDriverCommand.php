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

class ManageDriverCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('kaliop_queueing:managedriver')
            ->setDescription("Sends control commands to a driver")
            ->addArgument('action', InputArgument::REQUIRED, 'The action to execute. use "help" to see all available')
            ->addOption('driver', 'b', InputOption::VALUE_OPTIONAL, 'The driver (string), if not default', null)
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

        $driverName = $input->getOption('driver');
        $command = $input->getArgument('action');

        $manager = $this->getContainer()->get('kaliop_queueing.driverManager');

        if ($command == 'help') {
            $this->writeln("Available actions: " . implode(', ', $manager->listActions($driverName)));
            return;
        }

        if (!in_array($command, $manager->listActions($driverName))) {
            $this->writeln("Unrecognized action $command\nAvailable actions: " . implode(', ', $manager->listActions($driverName)));
            return;
        }

        $result = $manager->executeAction($command, $driverName);

        $this->writeln("Sent '$command' to driver $driverName");

        if ($result != '') {
            if (is_array($result)) {
                $result = print_r($result, true);
            }
            $this->writeln("Result: $result");
        }
    }
}