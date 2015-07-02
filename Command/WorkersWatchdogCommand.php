<?php

namespace Kaliop\QueueingBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Kaliop\QueueingBundle\Helper\Watchdog;
use Symfony\Component\Process\Exception\RuntimeException;
use Kaliop\QueueingBundle\Helper\BaseCommand;

/**
 * Checks if all the desired worker process are running, restarts dead ones
 *
 * @todo in 'check' mode display uptime of worker processes
 * @todo add 'list' mode to display list of existing groups
 */
class WorkersWatchdogCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName( 'kaliop_queueing:workerswatchdog' )
            ->addArgument( 'mode', InputArgument::OPTIONAL, 'start/stop/check workers', 'start' )
            ->addOption( 'group', 'g', InputOption::VALUE_REQUIRED, 'Use this, along with config, to create different sets of workers to run on different servers' )
            ->setDescription( 'Checks that all configured worker processes are alive, restarts any found missing' )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $this->setOutput( $output );

        $this->writeln( "Watchdog started at " . $this->formatDate(), OutputInterface::VERBOSITY_VERY_VERBOSE );

        $command = $input->getArgument( 'mode' );
        if ( !in_array( $command, array( 'start', 'stop', 'check' ) ) )
        {
            throw new \InvalidArgumentException( "Mode '$command' is not valid" );
        }

        $groupName = $input->getOption( 'group' );
        if ( $groupName == '' )
        {
            $groupName = 'default';
        }

        // by default we do not force an environment on the commands we ant to execute.
        // We do if we have been invoked with one - which is checked using the tricky test below here
        $env = null;
        if ( $input->hasParameterOption( '--env' ) ||  $input->hasParameterOption( '-e' ) )
        {
            $env = $input->getOption( 'env' );
        }

        $manager = $this->getContainer()->get( 'kaliop_queueing.worker_manager.service' );
        $commandList = $manager->getWorkersCommands( $groupName, $env );
        $this->writeln( "Checking " . count( $commandList ) . " worker processes for group '$groupName'", OutputInterface::VERBOSITY_VERBOSE );

        $watchdog = new Watchdog();
        foreach( $commandList as $workerName => $cmd )
        {
            // To see if the command is executing, we need to retrieve a version of it which was not escaped for the shell
            // NB: this is most likely NOT failproof!
            $workerCommand = $manager->getWorkerCommand( $workerName, $groupName, $env, true );

            $this->writeln( "Looking for process with command line: $workerCommand", OutputInterface::VERBOSITY_VERBOSE );

            $pids = $watchdog->getProcessPidByCommand( $workerCommand );
            if ( count( $pids ) )
            {
                $pids = array_keys( $pids );

                switch( $command )
                {
                    case 'start':
                        $this->writeln( "Worker: $workerName, found pid: " . implode( ',', $pids ), OutputInterface::VERBOSITY_VERBOSE );
                        break;

                    case 'stop':
                        $this->writeln( "Stopping process: " . implode( ',', $pids ) );
                        $watchdog->stopProcesses( $pids );
                        break;

                    case 'check':
                        $this->writeln( "Worker: $workerName, found pid: " . implode( ',', $pids ), OutputInterface::VERBOSITY_NORMAL );
                }
            }
            else
            {
                switch( $command )
                {
                    case 'start':
                        $this->writeln( "Starting worker: $workerName", OutputInterface::VERBOSITY_VERBOSE );
                        try
                        {
                            $this->writeln( "Command: $cmd" );
                            $watchdog->startProcess( $cmd );
                        }
                        catch( RuntimeException $e )
                        {
                            $output->writeln( "Process can not be started! Reason: " . $e->getMessage() );
                        }
                        break;

                    case 'check':
                        $this->writeln( "Worker: $workerName: not started" );
                        break;
                }
            }
        }

        $this->writeln( "Watchdog ended at " . $this->formatDate(), OutputInterface::VERBOSITY_VERY_VERBOSE );
    }
}