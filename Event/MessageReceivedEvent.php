<?php

namespace Kaliop\QueueingBundle\Event;

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
        $this->message = $message;
        $this->consumer = $consumer;
    }

    public function getBody()
    {
        return $this->body;
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