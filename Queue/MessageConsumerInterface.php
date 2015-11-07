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
     * @deprecated the current message is now made available directly in Events, and the current consumer message should
     *             only be available to subclasses, not to the rest of the world...
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface|null
     */
    public function getCurrentMessage();
}
