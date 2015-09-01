<?php

namespace Kaliop\QueueingBundle\Adapter;

use Kaliop\QueueingBundle\Queue\Queue;

/**
 * Drivers are tasked to
 * 1. manage MessageProducer objects, tasked to send messages
 * 2. decode native messages into bundle messages
 * 3. enable/disable debug mode
 * 4. provide service ids for consumers
 */
interface DriverInterface
{
    // *** Producer side ***

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\ProducerInterface
     */
    public function getProducer($queueName);

    // *** Consumer side ***

    /**
     * @return string \Kaliop\QueueingBundle\Queue\ConsumerInterface
     */
    public function getConsumer($queueName);

    /**
     * Returns true if the driver can decode the native (as delivered by the networking layer) message
     *
     * @param mixed $msg
     * @return bool
     */
    public function acceptMessage($msg);

    /**
     * This will be called only after a successful acceptMessage()
     *
     * @param mixed $msg
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function decodeMessage($msg);

    // *** The dark side ;-) ***

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\QueueManagerInterface
     */
    public function getQueueManager($queueName);

    /**
     * @param bool $debug
     */
    public function setDebug($debug);
}
