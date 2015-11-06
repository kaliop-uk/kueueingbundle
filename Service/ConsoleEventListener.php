<?php

namespace Kaliop\QueueingBundle\Service;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Keeps a pointer to the executing Application. NB: even if recursive apps are used, it only keeps a ref to the original one
 */
class ConsoleEventListener
{
    protected $application;

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ($this->application == null) {
            $this->application = $event->getCommand()->getApplication();
        }
    }

    public function getCurrentApplication()
    {
        return $this->application;
    }
}
