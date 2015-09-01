<?php

namespace Kaliop\QueueingBundle\Queue;

/**
 * Modeled after the RabbitMqBundle consumer, so that it can be used by the consumer console command
 */
interface ConsumerInterface
{
    /**
     * @param int $limit
     */
    public function setMemoryLimit($limit);

    /**
     * @param string $key
     */
    public function setRoutingKey($key);

    /**
     * If $amount is null, loop forever
     *
     * @param int $amount
     * @return mixed
     */
    public function consume($amount);
}