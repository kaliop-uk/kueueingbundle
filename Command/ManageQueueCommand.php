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
            ->setName( 'kaliop_queueing:managequeue' )
            ->setDescription( "Sends control commands to a queue to f.e. purge it or grab some stats" )
            ->addArgument( 'action', InputArgument::REQUIRED, 'The action to execute. use "list" to see all available' )
            ->addArgument( 'queue_name', InputArgument::OPTIONAL, 'The queue name (string)', '' )
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Enable Debugging' )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->setOutput( $output );

        if ( defined( 'AMQP_DEBUG' ) === false )
        {
            define( 'AMQP_DEBUG', (bool) $input->getOption( 'debug' ) );
        }

        $command = $input->getArgument( 'action' );
        $queue = $input->getArgument( 'queue_name' );

        /// @var \Kaliop\QueueingBundle\Service\MessageProducer\ $messageBroker
        $messageBroker = $this->getContainer()->get( 'kaliop_queueing.message_producer.queue_control' );

        if ( $command == 'list' || $command == 'help' )
        {
            $this->writeln( "Available commands: " . implode( ', ', $messageBroker->listActions( $queue ) ) );
            return;
        }

        if ( ! in_array( $command, $messageBroker->listActions( $queue ) ) )
        {
            $this->writeln( "Unrecognized command $command\nAvailable commands: " . implode( ', ', $messageBroker->listActions( $queue ) ) );
            return;
        }

        $messageBroker->setQueueName( $queue );
        $result = $messageBroker->executeAction( $command );

        $this->writeln( "Sent $command to queue $queue" );

        if ( $result != '' )
        {
            if ( is_array( $result ) )
            {
                $result = print_r( $result, true );
            }
            $this->writeln( "Result: $result" );
        }
    }
}