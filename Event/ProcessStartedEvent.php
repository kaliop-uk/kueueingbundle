<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ProcessStartedEvent extends Event
{
    protected $pid;
    protected $command;

    public function __construct($pid, $command)
    {
        $this->pid = $pid;
        $this->command = $command;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getCommand()
    {
        return $this->pid;
    }
}