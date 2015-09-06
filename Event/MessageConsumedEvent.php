<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Kaliop\QueueingBundle\Queue\MessageInterface;

class MessageConsumedEvent extends Event
{
    protected $body;
    protected $message;
    protected $consumer;
    protected $consumptionResult;

    public function __construct($body, $consumptionResult, MessageInterface $message, $consumer)
    {
        $this->body = $body;
        $this->consumptionResult = $consumptionResult;
        $this->message = $message;
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

    public function getMessage()
    {
        return $this->message;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }
}