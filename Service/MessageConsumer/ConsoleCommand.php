<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer;

use Kaliop\QueueingBundle\Service\MessageConsumer;
use Kaliop\QueueingBundle\Command\ConsumerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\Process;

/**
 * This service can be registered to consume "execute sf console command" messages
 *
 * @todo return a value object (with arrayAccess) instead of an array, so that event listener can easily tell it apart from other stuff
 */
class ConsoleCommand extends MessageConsumer
{
    protected $consoleCommand;
    protected $application;
    protected $eventListener;
    /// these will not be used for the executed commands, even if present in received message
    protected $filteredOptions = array(
        // default symfony console options which make no sense when commands are executed headless
        'h', 'help', 'V', 's'
    );
    // these are always injected. NB: key -> value
    protected $forcedOptions = array(
        'n' => null
    );
    // this is used as basic filter against shell injection. Received options names must be compatible with they or will dropped
    protected $validOptionsRegexp = '/[a-zA-Z0-9_\-.]+/';

    public function __construct($consoleManager)
    {
        $this->consoleCommand = $consoleManager->getConsoleCommand();
    }

    public function setEventListener($listener)
    {
        $this->eventListener = $listener;
    }

    /*public function setApplication(Application $application)
    {
        $this->application = $application;
    }*/

    /**
     * @param array $body
     * @return array (positional) retcode, stdout, stderr
     * @throws \UnexpectedValueException
     */
    public function consume($body)
    {
        // validate members in $body
        if (
            !is_array($body) ||
            empty($body['command']) ||
            (isset($body['arguments']) && !is_array($body['arguments'])) ||
            (isset($body['options']) && !is_array($body['options']))
        ) {
            throw new \UnexpectedValueException("Message format unsupported: missing 'command' or bad 'arguments' or 'options'. Received: " . json_encode($body));
        }

        if (!isset($body['arguments'])) {
            $body['arguments'] = array();
        }
        if (!isset($body['options'])) {
            $body['options'] = array();
        }

        if ($this->eventListener) {
            $this->application = $this->eventListener->getCurrentApplication();
        }

        // for a speed/resource gain, we test: if command is not registered, do not try to run it
        $this->validateCommand($body['command'], $body['arguments'], $body['options']);

        return $this->runCommand($body['command'], $body['arguments'], $body['options']);
    }

    /**
     * Does some preliminary checks before attempting to run command, throws if command is blatantly non-runnable.
     * (split as a separate method to better accommodate subclasses)
     *
     * @param string $consoleCommand
     * @param array $arguments
     * @param array $options
     * @throws \InvalidArgumentException
     */
    protected function validateCommand($consoleCommand, $arguments = array(), $options = array())
    {
        if ($this->application !== null) {
            if (!in_array($consoleCommand, array_keys($this->application->all()))) {
                throw new \InvalidArgumentException("Command '$consoleCommand' is not registered in the symfony console");
            }
        }
    }

    /**
     * Runs an sf command as a separate php process - this way we insure the worker is stable (no memleaks or crashes)
     *
     * @param string $consoleCommand
     * @param array $arguments
     * @param array $options
     * @return array (positional) retcode, stdout, stderr
     * @throws ???
     *
     * @todo add support for ttl when executing commands
     * @todo add a verbose mode: echo to stdout or a log file the results of execution
     */
    protected function runCommand($consoleCommand, $arguments = array(), $options = array())
    {
        $command = $this->consoleCommand;

        $command .= $this->buildCommandString($consoleCommand, $arguments, $options);

        $label = trim(ConsumerCommand::getLabel());
        if ($label != '') {
            $label = " '$label'";
        }

        if ($this->logger) {
            $this->logger->debug("Console command will be executed from MessageConsumer{$label}: " . $command);
        }

        $process = new Process($command);
        $retCode = $process->run();

        $results = array($retCode, $process->getOutput(), $process->getErrorOutput());

        if ($retCode != 0 && $this->logger) {
            $this->logger->error(
                "Console command executed from MessageConsumer{$label} failed. Retcode: $retCode, Error message: '" . trim($results[2]) . "'",
                array());
        }

        return $results;
    }

    protected function buildCommandString($consoleCommand, $arguments = array(), $options = array())
    {
        $command = '';

        // forced options come before the command proper
        foreach ($this->getForcedOptions() as $opt => $value) {
            $command .= (strlen($opt) == 1 ? ' -' : ' --') . $opt;
            if ($value !== null) {
                $command .= (strlen($opt) == 1 ? '' : '=') . escapeshellarg($value);
            }
        }

        $command .= ' ' . escapeshellarg($consoleCommand);
        foreach ($arguments as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        /// @todo !important check if we can trim down this code by usage of \Symfony\Component\Console\Input\ArrayInput
        // options come after arguments to allow legacy scripts to be queued
        foreach ($options as $opt => $value) {
            // We allow callers to tell us how many dashes they want
            // If no dash is given, we use 1 for single letter options,
            $optName = ltrim($opt, '-');
            $dashes = strlen($opt) - strlen($optName);
            if ($dashes == 0) {
                $dashes = (strlen($optName) == 1) ? 1 : 2;
            }

            // silently drop undesirable options
            if (preg_match($this->validOptionsRegexp, $optName) &&
                !in_array($optName, $this->filteredOptions)
            ) {
                $command .= " " . str_repeat('-', $dashes) . $optName;
                if ($value !== null) {
                    // Options names with 1 dash use space, not equal sign. Or is it option name length which decides?
                    // According to http://mailutils.org/manual/html_node/Option-Basics.html:
                    // short opts (one dash, one letter), take an optional space and value, but space can not be used if value is optional
                    // long opts (one dash, one letter), take either equal or space then value, but space can not be used if value is optional
                    $command .= ($dashes == 1 ? '' : '=') . escapeshellarg($value);
                }
            } else {
                if ($this->logger) {
                    $this->logger->notice("Dropped option: '$opt'");
                }
            }
        }

        return $command;
    }

    protected function buildCommandArray($consoleCommand, $arguments = array(), $options = array())
    {
        $command = array();

        // forced options come before the command proper
        foreach ($this->getForcedOptions() as $opt => $value) {
            $realOpt = (strlen($opt) == 1 ? '-' : '--') . $opt;
            $command[$realOpt] = $value;
        }

        $command['command'] = $consoleCommand;

        foreach ($arguments as $arg) {
            $command[] = $arg;
        }

        /// @todo !important check if we can trim down this code by usage of \Symfony\Component\Console\Input\ArrayInput
        // options come after arguments to allow legacy scripts to be queued
        foreach ($options as $opt => $value) {
            // We allow callers to tell us how many dashes they want
            // If no dash is given, we use 1 for single letter options,
            $optName = ltrim($opt, '-');
            $dashes = strlen($opt) - strlen($optName);
            if ($dashes == 0) {
                $dashes = (strlen($optName) == 1) ? 1 : 2;
            }

            // silently drop undesirable options
            if (preg_match($this->validOptionsRegexp, $optName) &&
                !in_array($optName, $this->filteredOptions)
            ) {
                $opt = str_repeat('-', $dashes) . $optName;
                $command[$opt] = $value;
            } else {
                if ($this->logger) {
                    $this->logger->notice("Dropped option: '$opt'");
                }
            }
        }

        return $command;
    }

    protected function getForcedOptions()
    {
        $options = $this->forcedOptions;
        $env = ConsumerCommand::getForcedEnv();
        if ($env != '') {
            $options['env'] = $env;
        }
        return $options;
    }
}
