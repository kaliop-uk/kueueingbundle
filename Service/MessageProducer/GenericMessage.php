<?php

namespace Kaliop\QueueingBundle\Service\MessageProducer;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages which come already encoded
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class GenericMessage extends BaseMessageProducer
{
    /**
     * @param string $data
     * @param string $contentType if null, application/json is assumed. The $data will not be reencoded with it, but
     *                            depending on the protocol in use, this info might make it to the consumer
     * @param string $routingKey
     * @param int $ttl seconds for the message to live in the queue
     */
    public function publish($data, $contentType = null, $routingKey = '', $ttl = null)
    {
        $extras = array();
        if ($ttl) {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array('expiration' => $ttl * 1000);
        }

        if ($contentType != null) {
            $this->contentType = $contentType;
        }

        $this->doPublish($data, $routingKey, $extras);
    }

    /**
     * @param array $data
     * @param string $contentType
     * @param string $routingKey
     * @param int $ttl
     */
    public function batchPublish(array $data, $contentType = null, $routingKey = '', $ttl = null)
    {
        $extras = array();
        if ($ttl) {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array('expiration' => $ttl * 1000);
        }

        if ($contentType != null) {
            $this->contentType = $contentType;
        }

        $this->doBatchPublish($data, $routingKey, $extras);
    }

    /**
     * Since we expect to receive data already encoded, no need to reencode
     *
     * @param mixed $data
     * @return mixed
     */
    protected function encodeMessageBody($data)
    {
        return $data;
    }

    /**
     * We disable the check done in parent method that the content-type is supported for encoding
     *
     * @param string $type
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
    }
}
