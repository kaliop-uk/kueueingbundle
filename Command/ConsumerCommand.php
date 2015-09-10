<?php

namespace Kaliop\QueueingBundle\Command;

use OldSound\RabbitMqBundle\Command\ConsumerCommand as BaseCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adds a few more options on top of the standard rabbitmq:consumer command,
 * plus it traces the fact that the --env option was used on command line or not
 *
 * @todo decouple this command from rabbitmq...
 */
class ConsumerCommand extends BaseCommand
{
    /// @todo look if Sf allows the service we invoke to grab back a handle on this command instance, instead of using
    ///       static calls (might be doable with Sf 2.4 or later and services-as-commands)
    protected static $label;

    protected static $forcedEnv;

    protected $driver;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('kaliop_queueing:consumer')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'A name used to distinguish worker processes')
            ->addOption('driver', 'i', InputOption::VALUE_REQUIRED, 'The driver (string), if not default', null)
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'A timeout (seconds), after which stop the process', 0)
            ->setDescription("Starts a worker (message consumer) process");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$label = $input->getOption('label');

        // tricky test, as hasOption( 'env' ) always returns true
        if ($input->hasParameterOption('--env') || $input->hasParameterOption('-e')) {
            self::$forcedEnv = $input->getOption('env');
        }

        $driverName = $input->getOption('driver');
        $debug = $input->getOption('debug');
        $timeout = $input->getOption('timeout');

        $this->driver = $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        if ($debug !== null) {
            $this->driver->setDebug($debug);
        }

        // reimplementation of parent::execute($input, $output); to add timeout

        if (defined('AMQP_WITHOUT_SIGNALS') === false) {
            define('AMQP_WITHOUT_SIGNALS', $input->getOption('without-signals'));
        }

        if (!AMQP_WITHOUT_SIGNALS && extension_loaded('pcntl')) {
            if (!function_exists('pcntl_signal')) {
                throw new \BadFunctionCallException("Function 'pcntl_signal' is referenced in the php.ini 'disable_functions' and can't be called.");
            }

            pcntl_signal(SIGTERM, array(&$this, 'stopConsumer'));
            pcntl_signal(SIGINT, array(&$this, 'stopConsumer'));
            pcntl_signal(SIGHUP, array(&$this, 'restartConsumer'));
        }

        // this is now handled by the driver
        //if (defined('AMQP_DEBUG') === false) {
        //    define('AMQP_DEBUG', (bool) $input->getOption('debug'));
        //}

        $this->amount = $input->getOption('messages');

        if (0 > $this->amount) {
            throw new \InvalidArgumentException("The -m option should be null or greater than 0");
        }
        $this->initConsumer($input);

        $this->consumer->consume($this->amount, $timeout);

        // end reimplementation

        // reset label after execution is done, in case of weird usage patterns
        self::$label = null;
    }

    public static function getLabel()
    {
        return self::$label;
    }

    public static function getForcedEnv()
    {
        return self::$forcedEnv;
    }

    /**
     * Reimplemented to allow drivers to give us a Consumer
     * @param $input
     */
    protected function initConsumer($input) {
        $this->consumer = $this->driver->getConsumer($input->getArgument('name'));

        if (!is_null($input->getOption('memory-limit')) && ctype_digit((string) $input->getOption('memory-limit')) && $input->getOption('memory-limit') > 0) {
            $this->consumer->setMemoryLimit($input->getOption('memory-limit'));
        }
        $this->consumer->setRoutingKey($input->getOption('route'));
    }
}