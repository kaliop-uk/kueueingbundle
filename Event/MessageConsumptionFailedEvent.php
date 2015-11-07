<?php

namespace Kaliop\QueueingBundle\Event;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\MessageInterface;

class MessageConsumptionFailedEvent extends MessageEvent
{
    protected $exception;

    public function __construct(MessageInterface $message, $body, MessageConsumerInterface $consumer, \Exception $exception)
    {
        $this->message = $message;
        $this->body = $body;
        $this->consumer = $consumer;
        $this->exception = $exception;

    }

    public function getException()
    {
        return $this->exception;
    }
}