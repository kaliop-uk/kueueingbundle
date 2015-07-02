<?php

namespace Kaliop\QueueingBundle\Helper;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Used to check that the "daemon" processes we want to keep running are alive
 */
class Watchdog
{
    /**
     * Starts a process in the background - waits for 1 second to check that the process did not die prematurely
     * (it is supposed to be a long-running task)
     * @param string $command
     *
     * @return int the pid of the created process
     * @throws \Symfony\Component\Process\Exception\RuntimeException when process could not start / terminated immediately
     */
    public function startProcess( $command )
    {
        $process = new Process( $command );
        $process->start();
        // give the OS some time to abort invalid commands
        sleep( 1 );
        if ( !$process->isRunning() )
        {
            throw new RuntimeException( "Process terminated immediately" );
        }
        return $process->getPid();
    }

    /**
     * @todo be smarter, trying f.e. a SIGKILL if SIGTERM does not work for N secs
     * @param array $pids
     * @param int $signal
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function stopProcesses( array $pids, $signal = SIGTERM )
    {
        foreach( $pids as $pid )
        {
            if ( (int)$pid > 0 )
            {
                posix_kill( (int)$pid, $signal );
            }
            else
            {
                throw new RuntimeException( "Can not try to kill PID '$pid'" );
            }
        }
    }

    /**
     * Checks if a command is still executing, by looking at its exact command-line in the process list
     * NB: it is based on the ps command which, (on linux) removes most quotes in its output, compared to what is passed
     * on the shell for execution.
     *
     * For a non-exhaustive list of ways to express options, try using 'ps' and grep for the following:
     * php sleep.php -a='b' -c="d" -e='f g' --h="i j" k 'l' "m" 'n''' "o\"" "p'" 'q"' -r=\' -s=\'\' -t='u'\''v'
     *
     * @param string $command
     * @return array (key: pid, value: command)
     * @throws \Exception
     *
     * @todo windows support: use "tasklist /v"
     */
    public function getProcessPidByCommand( $command )
    {
        if ( strtoupper( substr( PHP_OS, 0, 3) ) === 'WIN' )
        {
            throw new \Exception( "Windows not supported yet" );
        }
        else
        {
            exec(
                // gotta love escaping single quotes in shell
                "ps -eo pid,args | fgrep '" . str_replace( "'", "'\''", $command ) . "' | fgrep -v fgrep",
                $output,
                $retCode );

            if ( $retCode != 0 )
            {
                return array();
            }

            $pids = array();
            foreach( $output as $line )
            {
                $line = explode( ' ', trim( $line ), 2 );
                $pids[$line[0]] = $line[1];
            }
            return $pids;
        }
    }
}