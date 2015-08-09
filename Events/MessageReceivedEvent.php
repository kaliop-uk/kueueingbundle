<?php

namespace Kaliop\QueueingBundle\Events;

use Symfony\Component\EventDispatcher\Event;
use PhpAmqpLib\Message\AMQPMessage;

class MessageReceivedEvent extends Event
{
    protected $body;
    protected $message;
    protected $consumer;

    public function __construct( $body, AMQPMessage $message, $consumer )
    {
        $this->body = $body;
        $this->consumer = $consumer;
    }

    public function getMessage()
    {
        return $this->body;
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