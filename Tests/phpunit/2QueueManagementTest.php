<?php

require_once(__DIR__.'/RabbitMQTest.php');

class QueueManagementTests extends RabbitMQTest
{
    public function testListQueues()
    {
        $queueManager = $this->getDriver()->getQueueManager(null);
        $this->assertArrayHasKey('travis_test', $queueManager->executeAction('list'));
    }

    public function testQueueInfo()
    {
        $queueManager = $this->getQueueManager('travis_test');
        $info = $queueManager->executeAction('info');
        $this->assertInternalType('array', @$info['queue_options']);
    }

    public function testQueuePurge()
    {
        $queueManager = $this->getQueueManager('travis_test');
        $this->assertInternalType('integer', $queueManager->executeAction('purge'));
    }
}
