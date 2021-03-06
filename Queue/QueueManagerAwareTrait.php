<?php

namespace Kaliop\QueueingBundle\Queue;

trait QueueManagerAwareTrait
{
    /** @var QueueManagerInterface $queueManager */
    protected $queueManager;

    public function setQueueManager(QueueManagerInterface $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    protected function getQueueManagerInternal()
    {
        return $this->queueManager;
    }
}
