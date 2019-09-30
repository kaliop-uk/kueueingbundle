<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class RabbitMQTest extends WebTestCase
{
    protected function setUp()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
    }

    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected function getDriver()
    {
        return $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver('rabbitmq');
    }

    protected function getQueueManager($queueName= '')
    {
        return $this->getDriver()->getQueueManager($queueName);
    }

    protected function getConsumer($queueName)
    {
        return $this->getDriver()->getConsumer($queueName);
    }

    protected function getMsgProducer($msgProducerServiceId, $queueName)
    {
        return $this->getContainer()->get($msgProducerServiceId)
            ->setDriver($this->getDriver())
            ->setQueueName($queueName)
        ;
    }

    protected function purgeQueues()
    {
        foreach(func_get_args() as $queueName) {
            try {
                $this->getQueueManager($queueName)->executeAction('purge');
            } catch(PhpAmqpLib\Exception\AMQPProtocolChannelException $e) {
                // it's ok if queues to be purged do not exist yet
                echo "Error while purging queue '$queueName': " . $e->getMessage();
            }
        }
    }
}
