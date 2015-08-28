<?php

namespace Kaliop\QueueingBundle\Service\MessageProducer;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;

/**
 * Pushes messages used to distribute execution of http requests
 *
 * The routing key corresponds to the url, with slashes and doublecolons replaced by dots, to allow wildcard binding
 *
 * @todo test if expiration is actually upheld by rabbit
 */
class HTTPRequest extends BaseMessageProducer
{
    /**
     * @param string $url
     * @param array $options All CURL options are accepted
     * @param null $ttl
     */
    public function publish($url, $options = array(), $ttl = null)
    {
        $msg = array(
            'url' => $command,
            'options' => $options
        );
        $extras = array();
        if ($ttl) {
            // we want to be able to set a per-message-ttl, which is not currently supported, see https://github.com/videlalvaro/RabbitMqBundle/issues/80
            // so we subclassed the producer class, and add an expiration (in millisecs)
            // see also http://www.rabbitmq.com/ttl.html
            $extras = array('expiration' => $ttl * 1000);
        }
        $this->doPublish($msg, str_replace(array(':', '/'), '.', $command), $extras);
    }
}