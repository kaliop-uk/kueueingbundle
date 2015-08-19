<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageReceivedEvent;

/**
 * A class which can be registered as listener in order to filter SymfonyService messages
 */
class HTTPRequestFilter
{
    protected $allowedServers;

    public function __construct( array $allowedServers )
    {
        $this->allowedServers = $allowedServers;
    }

    public function onMessageReceived(MessageReceivedEvent $event)
    {
        // filter out unwanted events
        if (! $event->getConsumer() instanceof \Kaliop\QueueingBundle\Service\MessageConsumer\HTTPRequest)
            return;

        $body = $event->getBody();
        $url = @$body['url'];
        $options = @$body['options'];
        if (empty($options)) {
            /// we leave it up to the consumer to respond to these messages...
            return;
        }

        if (!$this->isServiceAllowed($url, $options)) {
            $event->stopPropagation();
        }
    }

    protected function isServiceAllowed($url, $options) {

        $server = parse_url($url, PHP_URL_HOST);

        foreach($this->allowedServers as $allowedServer) {

            if (substr($allowedServer, 0, 7) === 'regexp:') {
                if (preg_match(substr($allowedServer, 7), $url)) {
                    return true;
                }
            }
            elseif ($allowedServer == $server) {
                return true;
            }
        }

        return false;
    }
}