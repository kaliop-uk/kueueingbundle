<?php

namespace Kaliop\QueueingBundle\Service\MessageProducer;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages used to distribute execution of Symfony services
 *
 * The default routing key corresponds to the service, with doublecolons replaced by dots, followed by the method, to allow wildcard binding
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class SymfonyService extends BaseMessageProducer
{
    /**
     * @param string $service the name of a Service
     * @param string $method the php method to invoke on it
     * @param array $arguments the arguments for method invocation
     * @param null $routingKey if null, it will be calculated automatically
     * @param null $ttl seconds for the message to live in the queue
     */
    public function publish($service, $method, $arguments = array(), $routingKey = null, $ttl = null)
    {
        $msg = array(
            'service' => $service,
            'method' => $method,
            'arguments' => $arguments
        );
        $extras = array();
        if ($ttl) {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array('expiration' => $ttl * 1000);
        }
        if ($routingKey === null) {
            $routingKey = $this->getRoutingKey($service, $method, $arguments);
        }
        $this->doPublish($msg, $routingKey, $extras);
    }

    protected function getRoutingKey($service, $method, $arguments = array())
    {
        return str_replace(':', '.', $service) . '.' . $method;
    }
}