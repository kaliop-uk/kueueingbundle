<?php

namespace Kaliop\QueueingBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class extended to create console commands, making life just a bit nicer
 */
class BaseCommand extends ContainerAwareCommand
{
    // used for logging to screen current date with uniform format
    /// @todo !important get it from configuration :-)
    protected static $DATE_FORMAT = '%Y/%m/%d - %H:%M:%S';

    /// @var \Symfony\Component\Console\Output\OutputInterface $output
    protected $output;

    /// Sf writeln api is braindead
    protected function writeln($msg, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        if ($this->output->getVerbosity() >= $verbosity) {
            $this->output->writeln($msg);
        }
    }

    protected function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    protected function formatDate($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return strftime(static::$DATE_FORMAT, $timestamp);
    }
}
