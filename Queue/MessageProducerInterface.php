<?php

namespace Kaliop\QueueingBundle\Queue;

use Kaliop\QueueingBundle\Adapter\DriverInterface;

interface MessageProducerInterface
{
    /**
     * @param \Kaliop\QueueingBundle\Adapter\DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver);

    /**
     * @param string $queue
     */
    public function setQueueName($queue);
}