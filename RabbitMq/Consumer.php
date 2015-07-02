<?php
/**
 * User: gaetano.giunta
 * Date: 19/05/14
 * Time: 21.40
 */

namespace Kaliop\QueueingBundle\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer as BaseConsumer;

/**
 * Extends the parent class to allow users to get access to the queue
 */
class Consumer extends BaseConsumer
{
    protected $queueStats = array();

    public function getQueueOptions()
    {
        return $this->queueOptions;
    }

    public function getExchangeOptions()
    {
        return $this->exchangeOptions;
    }

    public function getQueueStats()
    {
        return $this->queueStats;
    }

    /**
     * Reimplement the code from BaseAmqp, to save queue stats when we can
     */
    protected function queueDeclare()
    {
        if (null !== $this->queueOptions['name']) {
            list($queueName, $msgCount, $consumerCount) = $this->getChannel()->queue_declare($this->queueOptions['name'], $this->queueOptions['passive'],
                $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                $this->queueOptions['arguments'], $this->queueOptions['ticket']);
            if (isset($this->queueOptions['routing_keys']) && count($this->queueOptions['routing_keys']) > 0) {
                foreach ($this->queueOptions['routing_keys'] as $routingKey) {
                    $this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $routingKey);
                }
            } else {
                $this->getChannel()->queue_bind($queueName, $this->exchangeOptions['name'], $this->routingKey);
            }

            $this->queueStats = array(
                'message_count' => $msgCount,
                'consumer_count' => $consumerCount
            );

            $this->queueDeclared = true;
        }
    }
}