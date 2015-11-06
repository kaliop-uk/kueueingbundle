<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Kaliop\QueueingBundle\Command\ConsumerCommand;

/**
 * Instead of forking a php process to run a Symfony console command, runs it directly in-process.
 * This is expected to be:
 * - faster
 * - unsafe, as there is no guarantee against memory leaks, stale database connections etc...
 *
 * NB: the the Application HAS to be injected into this message consumer or a fatal error will be thrown
 */
class InProcessConsoleCommand extends ConsoleCommand
{
    protected function runCommand($consoleCommand, $arguments = array(), $options = array())
    {
        $input = new StringInput($this->buildCommandString($consoleCommand, $arguments, $options));

        $label = trim(ConsumerCommand::getLabel());
        if ($label != '') {
            $label = " '$label'";
        }

        if ($this->logger) {
            $this->logger->debug("console command will be executed in-process from MessageConsumer{$label}: " . (string)$input);
        }

        $kernel = $this->application->getKernel();
        // q: is this helpful / needed ?
        //$kernel->shutdown();
        //$kernel->boot();

        $applicationClass = get_class($this->application);
        $app = new $applicationClass($kernel);
        $app->setAutoExit(false);

        $output = new BufferedOutput();
        $retCode = $app->run($input, $output);

        $results = array($retCode, $output->fetch(), '');

        if ($retCode != 0 && $this->logger) {
            $this->logger->error(
                "Console command executed in-process from MessageConsumer{$label} failed. Retcode: $retCode, Output: '" . trim($results[1]) . "'",
                array());
        }

        return $results;
    }
}