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

    /**
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface|null
     */
    public function getCurrentMessage();
}
