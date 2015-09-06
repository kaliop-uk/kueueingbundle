<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;

class MessageConsumedEvent extends Event
{
    protected $body;
    protected $message;
    protected $consumer;
    protected $consumptionResult;

    public function __construct($body, $consumptionResult, MessageConsumerInterface $consumer)
    {
        $this->body = $body;
        $this->consumptionResult = $consumptionResult;
        $this->consumer = $consumer;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getConsumptionResult()
    {
        return $this->consumptionResult;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }
}