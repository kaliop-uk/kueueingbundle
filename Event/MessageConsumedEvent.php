<?php

namespace Kaliop\QueueingBundle\Event;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\MessageInterface;

class MessageConsumedEvent extends MessageEvent
{
    protected $consumptionResult;

    public function __construct(MessageInterface $message, $body, MessageConsumerInterface $consumer, $consumptionResult)
    {
        $this->message = $message;
        $this->body = $body;
        $this->consumer = $consumer;
        $this->consumptionResult = $consumptionResult;
    }

    public function getConsumptionResult()
    {
        return $this->consumptionResult;
    }
}
