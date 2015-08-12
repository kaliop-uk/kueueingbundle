<?php

namespace Kaliop\QueueingBundle\Services\MessageProducer;

use Kaliop\QueueingBundle\Services\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages which come already formatted
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class GenericMessage extends BaseMessageProducer
{
    public function publish( $data, $contentType=null, $routingKey='', $ttl=null )
    {
        $extras = array();
        if ( $ttl )
        {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array( 'expiration' => $ttl * 1000 );
        }

        if ($contentType == null) {
            $contentType = $this->getContentType();
        }

        // to avoid re-encoding, we partially reimplement here parent::doPublish
        $this->getProducerService()
            ->setContentType( $contentType )
            ->publish( $data, $routingKey, $extras );
    }
}