<?php

namespace Kaliop\QueueingBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\MessageInterface;

class MessageSentEvent extends Event
{
    protected $msgBody;
    protected $routingKey;
    protected $additionalProperties;

    public function __construct($msgBody, $routingKey, $additionalProperties)
    {
        $this->msgBody = $msgBody;
        $this->routingKey = $routingKey;
        $this->additionalProperties = $additionalProperties;
    }

    public function getMessageBody()
    {
        return $this->msgBody;
    }

    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    public function getAdditionalProperties()
    {
        return $this->additionalProperties;
    }
}
