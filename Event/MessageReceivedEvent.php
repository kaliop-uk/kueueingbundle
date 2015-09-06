<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;

class MessageReceivedEvent extends Event
{
    protected $body;
    protected $consumer;

    public function __construct($body, MessageConsumerInterface $consumer)
    {
        $this->body = $body;
        $this->consumer = $consumer;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }
}