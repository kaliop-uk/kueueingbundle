<?php
/**
 * User: gaetano.giunta
 * Date: 01/05/14
 * Time: 23.37
 */

namespace Kaliop\QueueingBundle\Command;

use Kaliop\QueueingBundle\Helper\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * A very simple "echo" cli command, useful for testing (f.e. the queues dedicated to cli commands)
 */
class EchoBackCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName( 'kaliop_queueing:echoback' )
            ->setDescription( "Echoes back the argument it receives, either to stdout or to a file" )
            ->addArgument( 'input', InputArgument::REQUIRED, 'What to echo (string)' )
            // NB: an option with a required value remains optional
            ->addOption( 'file', 'f', InputOption::VALUE_REQUIRED, 'A file name to append to', null )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->setOutput( $output );

        $time = explode( ' ', microtime() );
        $msg =
            'It is ' . strftime( static::$DATE_FORMAT, $time[1] ) . ":" . sprintf( '%03d', ( $time[0]*1000 ) ) .
            " and process with pid " . getmypid() . " on host " . gethostname() . " says: " .
            $input->getArgument( 'input' ) . "\n";

        echo $msg;

        $fileName = $input->getOption( 'file' );
        if ( $fileName != '' )
        {
           file_put_contents( $fileName, $msg, FILE_APPEND );
        }
    }
}