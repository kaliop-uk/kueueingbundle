<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Producer as BaseProducer;
use PhpAmqpLib\Message\AMQPMessage;
use Kaliop\QueueingBundle\Queue\ProducerInterface;

/**
 * Extends the parent class to add some extra parameters per-message when sending, and allow users to get access to the queue
 */
class Producer extends BaseProducer implements ProducerInterface
{
    protected $queueStats = array();

    public function publish($msgBody, $routingKey = '', $params = array())
    {
        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $msg = new AMQPMessage(
            $msgBody,
            array_merge(array('content_type' => $this->contentType, 'delivery_mode' => $this->deliveryMode), $params)
        );
        $this->getChannel()->basic_publish($msg, $this->exchangeOptions['name'], $routingKey);
    }

    public function batchPublish(array $messages)
    {
        $channel = $this->getChannel();

        foreach($messages as $message) {
            // we have to remap the received hash into a positional array
            $msgBody = $message['msgBody'];
            $additionalProperties = isset($message['additionalProperties']) ? $message['additionalProperties'] : array();

            $channel->batch_basic_publish(
                new AMQPMessage((string) $msgBody, array_merge($this->getBasicProperties(), $additionalProperties)),
                $this->exchangeOptions['name'],
                isset($message['routingKey']) ? (string)$message['routingKey'] : '',
                isset($message['mandatory']) ? (bool)$message['mandatory'] : false,
                isset($message['immediate']) ? (bool)$message['immediate'] : false,
                isset($message['ticket']) ? $message['ticket'] : null
            );
        }

        if ($this->autoSetupFabric) {
            $this->setupFabric();
        }

        $channel->publish_batch();
    }

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