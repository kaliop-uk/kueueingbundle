<?php

namespace Kaliop\QueueingBundle\Adapter;

use Kaliop\QueueingBundle\Queue\Queue;

/**
 * Drivers are tasked to
 * 1. manage MessageProducer objects, tasked to send messages
 * 2. decode native messages into bundle messages
 */
interface DriverInterface
{
    // *** Producer side ***

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\MessageProducerInterface
     */
    public function getMessageProducer($queueName);

    // *** Consumer side ***

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
}
