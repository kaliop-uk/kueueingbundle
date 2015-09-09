<?php

namespace Kaliop\QueueingBundle\Queue;

/**
 * Modeled after the RabbitMqBundle producer
 */
interface ProducerInterface
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
     * Optimized version of the publication routine.
     * Dumb drivers are expected to just loop over the array and execute a publish() call on each, while smart drivers
     * might have a dedicated routine.
     *
     * @param array $messages Format of the array:
     *                        - 'msgBody' string, mandatory
     *                        - 'routingKey' string
     *                        - 'additionalProperties' array
     *                        - 'mandatory' bool, used by AMQP driver
     *                        - 'immediate' bool, used by AMQP driver
     *                        - 'ticket' used by AMQP driver
     */
    public function batchPublish(array $messages);

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType($contentType);
}
