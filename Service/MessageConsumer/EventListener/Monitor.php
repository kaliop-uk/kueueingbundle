<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageConsumptionFailedEvent;
use Kaliop\QueueingBundle\Event\MessageReceivedEvent;
use Kaliop\QueueingBundle\Event\MessageConsumedEvent;

/**
 * A braindead class which can be used to debug consumption of queue messages, by being registered as listener.
 * It just echoes the messages to stdout.
 *
 * @todo inject the Sf logger and use it for output instead of plain echo?
 * @todo use more precise time logging, with fractional seconds
 * @todo add a flag to tell this class to either dump the payloads or not
 */
class Monitor
{
    public function onMessageReceived(MessageReceivedEvent $event)
    {
        /// @todo this might give php warnings
        ///       We could also check if Symfony\Component\VarDumper is available and use it instead...
        if (class_exists('Doctrine\Common\Util\Debug')) {
            echo "Received a message at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . \Doctrine\Common\Util\Debug::dump($event->getMessage(), 2, false, false) . "\n";
        } else {
            echo "Received a message at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . var_export($event->getMessage(), true) . "\n";
        }
    }

    public function onMessageConsumed(MessageConsumedEvent $event)
    {
        /// @todo this might give php warnings
        ///       We could also check if Symfony\Component\VarDumper is available and use it instead...
        if (class_exists('Doctrine\Common\Util\Debug')) {
            echo "Message finished consumption at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . \Doctrine\Common\Util\Debug::dump($event->getConsumptionResult(), 2, false, false) . "\n";
        } else {
            echo "Message finished consumption at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . var_export($event->getConsumptionResult(), true) . "\n";
        }
    }

    public function onMessageConsumptionFailed(MessageConsumptionFailedEvent $event)
    {
        /// @todo this might give php warnings
        ///       We could also check if Symfony\Component\VarDumper is available and use it instead...
        if (class_exists('Doctrine\Common\Util\Debug')) {
            echo "Message failed consumption at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . \Doctrine\Common\Util\Debug::dump($event->getException(), 2, false, false) . "\n";
        } else {
            echo "Message failed consumption at " . strftime('%Y/%m/%d - %H:%M:%S', time()) . ": " . var_export($event->getException(), true) . "\n";
        }
    }
}
