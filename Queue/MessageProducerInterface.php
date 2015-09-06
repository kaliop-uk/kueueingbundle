<?php

namespace Kaliop\QueueingBundle\Queue;

use Kaliop\QueueingBundle\Adapter\DriverInterface;

interface MessageProducerInterface
{
    /**
     * @param \Kaliop\QueueingBundle\Adapter\DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver);

    /**
     * @param string $queue
     * @return $this
     */
    public function setQueueName($queue);
}