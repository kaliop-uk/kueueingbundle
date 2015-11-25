<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageConsumptionFailedEvent;
use Kaliop\QueueingBundle\Event\MessageReceivedEvent;
use Kaliop\QueueingBundle\Event\MessageConsumedEvent;

/**
 * A class which can be subclassed and registered as event listener to make sure that your consumer processes do not hog down the server.
 *
 * How it works:
 * - every time a message is received or consumed, check for a set of conditions
 * - if any is triggered, dispatch the signal which will allow the consumer to execute a graceful stop
 *
 * NB: it only works when the consumer is running with signals enabled (without the '-w')
 *
 * NB: it is recommended to use this class to either check conditions before message consumption or after, but not both,
 *     to avoid excessive overhead
 *
 * To implement verification of any conditions, override the implementation of the check() function to add more.
 * Note that the maximum memory consumption, as well as a timeout, can be checked natively without the need for the event listener.
 */
class Watchdog
{
    protected $timeout = 0;
    protected $start = 0;

    /**
     * @param int $timeout seconds after the reception of the 1st message
     */
    public function __construct($timeout)
    {
        $this->timeout = $timeout;
    }

    public function onMessageReceived(MessageReceivedEvent $event)
    {
        $this->check();
    }

    public function onMessageConsumed(MessageConsumedEvent $event)
    {
        $this->check();
    }

    public function onMessageConsumptionFailed(MessageConsumptionFailedEvent $event)
    {
        $this->check();
    }

    /**
     * TO BE REIMPLEMENTED!
     * To gracefully stop the consumer when a given condition is met, use: posix_kill(posix_getpid(), SIGTERM);
     */
    protected function check()
    {
        throw new \Exception("The 'check' function has to be implemented to have a working watchdog");
    }
}
