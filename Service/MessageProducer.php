<?php

namespace Kaliop\QueueingBundle\Service;

use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Kaliop\QueueingBundle\Event\MessageSentEvent;
use Kaliop\QueueingBundle\Event\EventsList;
use Kaliop\QueueingBundle\Queue\MessageProducerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A helper class, exposed as service.
 *
 * All it does is to
 * - allow injection of the name of the producer via service configuration, allowing subclasses to be more nimble
 * - define a standard serialization format: json (and support 2 more)
 *
 * @todo it would be nice if we could force subclasses to implement a way to document their message format using e.g. jsonschema
 */
abstract class MessageProducer implements MessageProducerInterface
{
    protected $queue = null;
    protected $contentType = 'application/json';
    protected static $knownContentTypes = array(
        'application/json',
        'application/x-httpd-php-source',
        'vnd.php.serialized'
    );
    /** @var DriverInterface */
    protected $driver;
    protected $dispatcher;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver = null)
    {
        $this->driver = $driver;
    }

    /**
     * @param DriverInterface $driver
     * @return MessageProducer
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * NB: when used for RabbitMQ, the queue name is the name of the producer as defined in old_sound_rabbit_mq.producers,
     *     it is not the name of the actual amqp queue
     * @param string $queue
     * @return MessageProducer
     * @throws \UnexpectedValueException
     */
    public function setQueueName($queue)
    {
        if ($queue == '') {
            throw new \UnexpectedValueException("Queue name can not be empty");
        }
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queue;
    }

    /**
     * @return \Kaliop\QueueingBundle\Queue\ProducerInterface
     */
    protected function getProducerService()
    {
        return $this->driver->getProducer($this->getQueueName());
    }

    /**
     * Returns the content type which will be used to serialize messages
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Sets the content type which will be used to serialize messages. Fails if type is unsupported
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public function setContentType($type)
    {
        if (!in_array($type, static::$knownContentTypes)) {
            throw new \InvalidArgumentException("Content type '$type' is not supported");
        }
        $this->contentType = $type;
    }

    /**
     * Encodes data according to current content type.
     * If you reimplement this in a subclass, do not forget to also add new types to self::$knownContentTypes
     * @param mixed $data
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function encodeMessageBody($data)
    {
        switch ($this->contentType) {
            case 'application/json':
                return json_encode($data);
            case 'application/x-httpd-php-source':
                return var_export($data, true);
            case 'vnd.php.serialized':
                return serialize($data);
            default:
                throw new \UnexpectedValueException("Serialization format unsupported: " . $this->contentType);
        }
    }

    /**
     * Some sugar for subclasses
     *
     * NB: the "extras" parameter only works as long as our customized class is used instead of Kaliop\QueueingBundle\RabbitMq\Producer
     * (this happens naturally when this bundle is properly configured, as it is out of the box)
     *
     * @param mixed $data
     * @param string $routingKey
     * @param array $extras
     */
    protected function doPublish($data, $routingKey = '', $extras = array())
    {
        $producer = $this->getProducerService();
        $producer->setContentType($this->getContentType());
        $body = $this->encodeMessageBody($data);
        $producer->publish($body, $routingKey, $extras);

        if ($this->dispatcher) {
            $event = new MessageSentEvent($body, $routingKey, $extras);
            $this->dispatcher->dispatch(EventsList::MESSAGE_SENT, $event);
        }
    }

    /**
     * @param array $data
     * @param string|array $routingKey if an array, it must have the same keys as $data
     * @param array $extras
     */
    protected function doBatchPublish(array $data, $routingKey = '', $extras = array())
    {
        if (is_string($routingKey)) {
            $routingKey = array_fill_keys(array_keys($data), $routingKey);
        }
        $producer = $this->getProducerService();
        $producer->setContentType($this->getContentType());
        $messages = array();
        $events = array();
        foreach($data as $key => $element) {
            $body = $this->encodeMessageBody($element);
            $messages[] = array(
                'msgBody' => $body,
                'routingKey' => $routingKey[$key],
                'additionalProperties' => $extras
            );
            $events[] = new MessageSentEvent($body, $routingKey, $extras);
        }

        $producer->batchPublish($messages);

        if ($this->dispatcher) {
            foreach ($events as $event) {
                $this->dispatcher->dispatch(EventsList::MESSAGE_RECEIVED, $event);
            }
        }
    }
}
