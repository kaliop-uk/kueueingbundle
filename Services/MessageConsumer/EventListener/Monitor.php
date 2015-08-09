<?php

namespace Kaliop\QueueingBundle\Services\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Events\MessageReceivedEvent;

/**
 * A braindead class which can be used to debug consumption of queue messages, by being registered as listener.
 */
class Monitor
{
    public function onMessageReceived(MessageReceivedEvent $event)
    {
        echo "Received a message at " . strftime( '%Y/%m/%d - %H:%M:%S', time() ) . ": " . var_export( $event->getMessage(), true ) . "\n";
    }
}