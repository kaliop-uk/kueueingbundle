<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Driver extends ContainerAware implements DriverInterface
{
    public function acceptMessage($message)
    {
        return $message instanceof \PhpAmqpLib\Message\AMQPMessage;
    }

    /**
     * @param AMQPMessage $message
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function decodeMessage($message)
    {
        return new Message($message);
    }

    /**
     * @param $queueName
     * @return \Kaliop\QueueingBundle\Queue\MessageProducerInterface
     */
    public function getMessageProducer($queueName)
    {
        return $this->container->get('old_sound_rabbit_mq.' . $queueName .'_producer');
    }
}