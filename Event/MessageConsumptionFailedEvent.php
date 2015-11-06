<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;

class MessageConsumptionFailedEvent extends Event
{
    protected $body;
    protected $consumer;
    protected $exception;

    public function __construct($body, \Exception $exception, MessageConsumerInterface $consumer)
    {
        $this->body = $body;
        $this->exception = $exception;
        $this->consumer = $consumer;
    }

    /**
     * The raw data received from the queueing driver
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function getMessage()
    {
        return $this->consumer->getCurrentMessage();
    }

    /**
     * The decoded data received from the queueing driver
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }
}