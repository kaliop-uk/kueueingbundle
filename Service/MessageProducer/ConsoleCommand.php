<?php

namespace Kaliop\QueueingBundle\Service\MessageProducer;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages used to distribute execution of symfony console commands
 *
 * The routing key corresponds to the command name, with doublecolons replaced by dots, to allow wildcard binding
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class ConsoleCommand extends BaseMessageProducer
{
    public function publish($command, $arguments = array(), $options = array(), $routingKey = null, $ttl = null)
    {
        $msg = array(
            'command' => $command,
            'arguments' => $arguments,
            'options' => $options
        );
        $extras = array();
        if ($ttl) {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array('expiration' => $ttl * 1000);
        }
        if ($routingKey === null) {
            $routingKey = $this->getRoutingKey($command, $arguments, $options);
        }
        $this->doPublish($msg, $routingKey, $extras);
    }

    /**
     * @param array $messages for each item: command, arguments, options
     * @param null $routingKey
     * @param null $ttl
     */
    public function batchPublish(array $messages, $routingKey = null, $ttl = null)
    {
        $extras = array();
        if ($ttl) {
            $extras = array('expiration' => $ttl * 1000);
        }
        if ($routingKey === null) {
            $routingKey = array();
            foreach($messages as $key => $message) {
                $routingKey[$key] = $this->getRoutingKey($message['command'], @$message['arguments'], @$message['options']);
            }
        }
        $this->doBatchPublish($messages, $routingKey, $extras);

    }

    protected function getRoutingKey($command, $arguments = array(), $options = array())
    {
        return str_replace(':', '.', $command);
    }
}