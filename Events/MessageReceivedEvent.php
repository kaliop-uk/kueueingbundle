<?php

namespace Kaliop\QueueingBundle\Events;

use Symfony\Component\EventDispatcher\Event;
use PhpAmqpLib\Message\AMQPMessage;

class MessageReceivedEvent extends Event
{
    protected $msg;
    protected $body;

    public function __construct( AMQPMessage $msg, $body )
    {
        $this->msg = $msg;
        $this->body = $body;
    }

    public function getMessage()
    {
        return $this->body;
    }

    public function getBody()
    {
        return $this->body;
    }
}