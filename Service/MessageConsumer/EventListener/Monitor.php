<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageReceivedEvent;

/**
 * A braindead class which can be used to debug consumption of queue messages, by being registered as listener.
 *
 * @todo inject the Sf logger and use it for output instead of plain echo?
 */
class Monitor
{
    public function onMessageReceived(MessageReceivedEvent $event)
    {
        /// @todo this might give php warnings
        ///       We could also check if Symfony\Component\VarDumper is available and use it instead...
        if (class_exists('Doctrine\Common\Util\Debug')) {
            echo "Received a message at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . \Doctrine\Common\Util\Debug::dump($event->getMessage()) . "\n";
        } else {
            echo "Received a message at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . var_export($event->getMessage(), true) . "\n";
        }


    }
}
