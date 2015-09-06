<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Queue\Queue;
use PhpAmqpLib\Message\AMQPMessage;
use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Driver extends ContainerAware implements DriverInterface
{
    public function getConsumer($queueName)
    {
        return $this->container->get('old_sound_rabbit_mq.' . $queueName . '_consumer');
    }

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
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\ProducerInterface
     */
    public function getProducer($queueName)
    {
        return $this->container->get('old_sound_rabbit_mq.' . $queueName . '_producer');
    }

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\QueueManagerInterface
     */
    public function getQueueManager($queueName)
    {
        $mgr = $this->container->get('kaliop_queueing.amqp.queue_manager');
        $mgr->setQueueName($queueName);
        return $mgr;
    }

    /**
     * @param bool $debug
     * @return Driver
     * @todo emit a warning if AMQP_DEBUG is already defined
     */
    public function setDebug($debug)
    {
        if (defined('AMQP_DEBUG') === false) {
            define('AMQP_DEBUG', (bool)$debug);
        }

        return $this;
    }
}