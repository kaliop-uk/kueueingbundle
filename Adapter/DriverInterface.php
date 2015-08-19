<?php

namespace Kaliop\QueueingBundle\Adapter;

/**
 * Drivers are tasked to decode native messages into bundle messages
 */
interface DriverInterface
{
    /**
     * Returns true if the driver can decode the native message
     *
     * @param mixed $msg
     * @return bool
     */
    public function acceptMessage($msg);

    /**
     * @param mixed $msg
     * @return Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function decodeMessage($msg);
}