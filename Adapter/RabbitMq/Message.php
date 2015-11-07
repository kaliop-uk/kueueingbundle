<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Queue\MessageInterface;

class Message implements MessageInterface
{
    protected $amqpMessage;

    public function __construct(AMQPMessage $msg)
    {
        $this->amqpMessage = $msg;
    }

    public function getBody()
    {
        return $this->amqpMessage->body;
    }

    /** @return string */
    public function getContentType()
    {
        //return $this->amqpMessage->content_encoding;
        return $this->amqpMessage->get('content_type');
    }

    public function getQueueName()
    {
        return $this->amqpMessage->getQueueName();
    }

    /**
     * Check whether a property exists in the 'properties' dictionary
     * ...to be determined:...  or if present - in the 'delivery_info' dictionary.
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->amqpMessage->has($name);
    }

    /**
     * @param string $name
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function get($name)
    {
        return $this->amqpMessage->get($name);
    }

    /**
     * Returns the properties content
     * @return array
     */
    public function getProperties()
    {
        return $this->amqpMessage->get_properties();
    }
}