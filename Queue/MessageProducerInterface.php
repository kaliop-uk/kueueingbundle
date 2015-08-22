<?php

namespace Kaliop\QueueingBundle\Queue;

/**
 * Modeled after the RabbitMqBundle producer:
 *
 */
interface MessageProducerInterface
{

    /**
     * Publishes the message and does what he wants with the properties
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array());

    /**
     * @param string $contentType
     */
    public function setContentType($contentType);
}