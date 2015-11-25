<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Kaliop\QueueingBundle\Queue\SignalHandlingConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Consumer as BaseConsumer;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as BaseAMQPMessage;

/**
 * Extends the parent class to allow users to get access to the queue and set a timeout to consume() calls
 */
class Consumer extends BaseConsumer implements ConsumerInterface, SignalHandlingConsumerInterface
{
    protected $queueStats = array();
    protected $loopBegin = null;
    protected $queueName;
    protected $dispatchSignals = false;

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

    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * Reimplement the code from BaseAmqp, to save queue stats when we can
     */
    protected function queueDeclare()
    {
        if (null !== $this->queueOptions['name']) {
            list($queueName, $msgCount, $consumerCount) = $this->getChannel()->queue_declare(
                $this->queueOptions['name'], $this->queueOptions['passive'],
                $this->queueOptions['durable'], $this->queueOptions['exclusive'],
                $this->queueOptions['auto_delete'], $this->queueOptions['nowait'],
                $this->queueOptions['arguments'], $this->queueOptions['ticket']
            );
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
     * Overridden to make it fluent, plus accept an object as well
     * @param callable|\OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface $callback
     * @return Consumer
     */
    public function setCallback($callback)
    {
        if ($callback instanceof \OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface) {
            $callback = array($callback, 'execute');
        }
        $this->callback = $callback;

        return $this;
    }

    /**
     * Set the memory limit - overridden to make it fluent
     *
     * @param int $memoryLimit MB
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
     * @throws \RuntimeException
     */
    public function setRoutingKey($routingKey)
    {
        // we have to throw an exception, otherwise the new routing key will just be ignored
        if ($this->queueDeclared && $this->routingKey != $routingKey) {
            throw new \RuntimeException('AMQP Consumer can not use a new routing key: queue has already been declared');
        }
        $this->routingKey = $routingKey;

        return $this;
    }

    /**
     * Overridden to add support for timeout
     * @param int $msgAmount
     * @param int $timeout
     */
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

    /**
     * Overridden to inject the queue name into the AMQP message
     * @param BaseAMQPMessage $msg
     */
    public function processMessage(BaseAMQPMessage $msg)
    {
        $newMsg = new AMQPMessage($msg->body, $msg->get_properties());
        $newMsg->delivery_info = $msg->delivery_info;
        $newMsg->body_size = $msg->body_size;
        $newMsg->is_truncated = $msg->is_truncated;
        $newMsg->setQueueName($this->queueName);

        $processFlag = call_user_func($this->callback, $newMsg);
        $this->handleProcessMessage($newMsg, $processFlag);
    }

    public function setHandleSignals($doHandle)
    {
        if (defined('AMQP_WITHOUT_SIGNALS') === false) {
            define('AMQP_WITHOUT_SIGNALS', !$doHandle);
        } elseif (AMQP_WITHOUT_SIGNALS != (!$doHandle)) {
            /// @todo throw an exception
        }

        $this->dispatchSignals = $doHandle;
    }

    public function forceStop($reason = '')
    {
        $this->forceStopConsumer();
    }

    protected function maybeStopConsumer()
    {
        if ($this->dispatchSignals) {
            pcntl_signal_dispatch();
        }

        if ($this->forceStop || ($this->consumed == $this->target && $this->target > 0)) {
            $this->stopConsuming();
        } else {
            return;
        }
    }
}