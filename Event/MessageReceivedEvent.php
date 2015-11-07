<?php

namespace Kaliop\QueueingBundle\Event;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\MessageInterface;

class MessageReceivedEvent extends MessageEvent
{
    public function __construct(MessageInterface $message, $body, MessageConsumerInterface $consumer)
    {
        $this->message = $message;
        $this->body = $body;
        $this->consumer = $consumer;
    }
}