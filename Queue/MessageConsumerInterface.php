<?php

namespace Kaliop\QueueingBundle\Queue;


interface MessageConsumerInterface
{
    /**
     * The function called by the 'consume messages' loop
     *
     * @param mixed $msg
     */
    public function receive($msg);
}