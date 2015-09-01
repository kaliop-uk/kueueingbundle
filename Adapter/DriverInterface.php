<?php

namespace Kaliop\QueueingBundle\Adapter;

use Kaliop\QueueingBundle\Queue\Queue;

/**
 * Drivers are tasked to
 * 1. build Producer objects, tasked to send messages
 * 2. build Consumer objects, tasked to receive messages in a loop and forward them to MessageConsumers
 * 3. decode native messages into bundle messages
 * 4. enable/disable debug mode
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
