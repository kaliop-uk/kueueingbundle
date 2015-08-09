<?php

namespace Kaliop\QueueingBundle\Services\MessageProducer;

use Kaliop\QueueingBundle\Services\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages used to distribute execution of xmlrpc calls
 *
 * The routing key corresponds to the server url followed by method name, with doublecolons and slashes replaced by dots, to allow wildcard binding
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class XmlrpcCall extends BaseMessageProducer
{
    public function publish( $server, $method, $arguments=array(), $ttl=null )
    {
        $msg = array(
            'server' => $server,
            'method' => $method,
            'arguments' => $arguments,
        );
        $extras = array();
        if ( $ttl )
        {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array( 'expiration' => $ttl * 1000 );
        }
        $this->doPublish( $msg, str_replace( array( ':', '/' ), '.', $server . '.' . $method ), $extras );
    }
}