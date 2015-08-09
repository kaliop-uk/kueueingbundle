<?php

namespace Kaliop\QueueingBundle\Services\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Events\MessageReceivedEvent;

/**
 * A class which can be registered as listener in order to filter ConsoleCommand messages
 */
class ConsoleCommandFilter
{
    public function onMessageReceived(MessageReceivedEvent $event)
    {
        /// @todo...
    }
}