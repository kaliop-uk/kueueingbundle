<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Consumer as BaseConsumer;
use \PhpAmqpLib\Exception\AMQPTimeoutException;

/**
 * Extends the parent class to allow users to get access to the queue and set a timeout to consume() calls
 */
class Consumer extends BaseConsumer implements ConsumerInterface
{
    protected $queueStats = array();
    protected $loopBegin = null;

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

    /**
     * Set the memory limit - overridden to make it fluent
     *
     * @param int $memoryLimit
     * @return Consumer
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;

        return $this;
    }

    /**
     * Overridden to make it fluent
     * @param string $routingKey
     * @return Consumer
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;

        return $this;
    }

    public function consume($msgAmount, $timeout=0)
    {
        if ($timeout > 0) {
            // save initial time
            $loopBegin = time();
            $remaining = $timeout;

            // reimplement parent::consume() to inject the timeout
            $this->target = $msgAmount;
            $this->setupConsumer();
            while (count($this->getChannel()->callbacks)) {
                // avoid waiting more than timeout seconds for message reception
                $this->setIdleTimeout($remaining);
                $this->maybeStopConsumer();
                try {
                    $this->getChannel()->wait(null, true, $this->getIdleTimeout());
                } catch (AMQPTimeoutException $e) {
                    return;
                }
                $remaining = $loopBegin + $timeout - time();
                if ($remaining <= 0) {
                    $this->forceStopConsumer();
                }
            }
        } else {
            $this->loopBegin = null;
            parent::consume($msgAmount);
        }
    }
}