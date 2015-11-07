<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class MessageEvent extends Event
{
    protected $message;
    protected $body;
    protected $consumer;

    /**
     * The message received from the queueing driver
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * The decoded data received from the queueing driver
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }
}
