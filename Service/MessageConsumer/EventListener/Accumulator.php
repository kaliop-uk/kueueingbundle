<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageConsumedEvent;

/**
 * A braindead class which can be used to debug consumption of queue messages, by being registered as listener.
 * It just holds the results of the last execution of consume() by the MessageConsumer
 */
class Accumulator
{
    protected $result;

    public function onMessageConsumed(MessageConsumedEvent $event)
    {
        $this->result = $event->getConsumptionResult();
    }

    public function getConsumptionResult()
    {
        return $this->result;
    }

    public function reset()
    {
        $this->result = null;
    }
}
