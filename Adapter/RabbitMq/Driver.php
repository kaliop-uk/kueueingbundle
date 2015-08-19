<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use Kaliop\QueueingBundle\Adapter\DriverInterface;

class Driver implements DriverInterface
{
    public function acceptMessage($message)
    {
        return $message instanceof \PhpAmqpLib\Message\AMQPMessage;
    }

    /**
     * @AMQPMessage $message
     * @return Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function decodeMessage($message)
    {
        return new Message($message);
    }
}