<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageConsumedEvent;

/**
 * A braindead class which can be used to debug consumption of queue messages, by being registered as listener.
 * It just holds the results of the last execution of consume() by the MessageConsumer
 */
class Accumulator
{
    protected $results = array();

    public function onMessageConsumed(MessageConsumedEvent $event)
    {
        $this->results[] = $event->getConsumptionResult();
    }

    /**
     * @param int $index if null, the last result is returned
     * @return mixed
     */
    public function getConsumptionResult($index = null)
    {
        if ($index === null) {
            return end($this->results);
        }
        if ($index >= count($this->results)) {
            throw new \RuntimeException("Accumulator has stored ".count($this->results)." results, can not retrieve the one at index $index");
        }
        return $this->results[$index];
    }

    /**
     * @return int
     */
    public function countConsumptionResult()
    {
        return count($this->results);
    }

    public function reset()
    {
        $this->results = array();
    }
}
