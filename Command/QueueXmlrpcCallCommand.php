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
class QueueXmlrpcCallCommand extends BaseCommand
{
    /// @var \Symfony\Component\Console\Output\OutputInterface $output
    protected $output;

    protected function configure()
    {
        $this
            ->setName( 'kaliop_queueing:queuexmlrpc' )
            ->setDescription( "Sends to a queue a message to execute an xmlrpc call" )
            ->addArgument( 'queue_name', InputArgument::REQUIRED, 'The queue name (string)' )
            ->addArgument( 'server', InputArgument::REQUIRED, 'The server to call (string)' )
            ->addArgument( 'method', InputArgument::REQUIRED, 'The method to call (string)' )
            ->addArgument( 'argument', InputArgument::IS_ARRAY, 'Arguments and options for the executed command' )
            ->addOption( 'ttl', 't', InputOption::VALUE_OPTIONAL, 'Validity of message (in seconds)', null )
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
            define( 'AMQP_DEBUG', (bool)$input->getOption( 'debug' ) );
        }

        $queue = $input->getArgument( 'queue_name' );
        $server = $input->getArgument( 'server' );
        $method = $input->getArgument( 'method' );
        $arguments = $input->getArgument( 'argument' );

        $messageProducer = $this->getContainer()->get( 'kaliop_queueing.message_producer.xmlrpc_call.service' );
        $messageProducer->setQueueName( $queue );
        try
        {
            $messageProducer->publish(
                $server,
                $method,
                $arguments,
                $ttl = $input->getOption( 'ttl' )
            );

            $this->writeln( "Xmlrpc call queued for execution" . ( $ttl ? ", will be valid for $ttl seconds" : '' ) );
        }
        catch( ServiceNotFoundException $e )
        {
            throw new \InvalidArgumentException( "Queue '$queue' is not registered" );
        }

    }

}