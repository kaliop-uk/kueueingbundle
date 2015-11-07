<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage as BaseMessage;

class AMQPMessage extends BaseMessage
{
    protected $queueName;

    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    public function getQueueName()
    {
        return $this->queueName;
    }
}
