<?php

namespace Kaliop\QueueingBundle\Service\MessageProducer;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages used to distribute execution of xmlrpc calls
 *
 * The routing key corresponds to the server url followed by method name, with doublecolons and slashes replaced by dots, to allow wildcard binding
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class XmlrpcCall extends BaseMessageProducer
{
    /**
     * @param string $server hostname of the xmlrpc server to contact
     * @param string $method the method to execute
     * @param array $arguments parameters fpr the method
     * @param null $routingKey if null it will be calculated based on server name + method
     * @param null $ttl seconds for the message to live in the queue
     */
    public function publish($server, $method, $arguments = array(), $routingKey = null, $ttl = null)
    {
        $msg = array(
            'server' => $server,
            'method' => $method,
            'arguments' => $arguments,
        );
        $extras = array();
        if ($ttl) {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array('expiration' => $ttl * 1000);
        }
        if ($routingKey === null) {
            $routingKey = $this->getRoutingKey($server, $method, $arguments);
        }
        $this->doPublish($msg, $routingKey, $extras);
    }

    protected function getRoutingKey($server, $method, $arguments = array())
    {
        return str_replace(array(':', '/'), '.', $server . '.' . $method);
    }
}
