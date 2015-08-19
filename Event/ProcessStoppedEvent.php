<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ProcessStoppedEvent extends Event
{
    protected $pid;

    public function __construct( $pid )
    {
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }
}