<?php

namespace Kaliop\QueueingBundle\Queue;


interface QueueManagerInterface
{
    /**
     * Will be called before listActions and executeAction
     * @param string $queue
     * @return $this
     */
    public function setQueueName($queue);

    /**
     * @return string[]
     */
    public function listActions();

    /**
     * @param string $action
     */
    public function executeAction($action);

}