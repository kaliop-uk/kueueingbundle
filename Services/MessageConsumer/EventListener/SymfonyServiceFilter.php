<?php

namespace Kaliop\QueueingBundle\Services\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Events\MessageReceivedEvent;

/**
 * A class which can be registered as listener in order to filter SymfonyService messages
 */
class SymfonyServiceFilter
{
    protected $allowedServices;

    public function __construct( array $allowedServices )
    {
        $this->allowedServices = $allowedServices;
    }

    public function onMessageReceived(MessageReceivedEvent $event)
    {
        // filter out unwanted events
        if (! $event->getConsumer() instanceof \Kaliop\QueueingBundle\Services\MessageConsumer\SymfonyService)
            return;

        $body = $event->getBody();
        $service = @$body['service'];
        $method = @$body['method'];
        if (empty($service) || empty($method)) {
            /// we leave it up to the consumer to respond to these messages...
            return;
        }

        if (!$this->isServiceAllowed($service, $method)) {
            $event->stopPropagation();
        }
    }

    protected function isServiceAllowed($service, $method) {
        foreach($this->allowedServices as $allowedService => $allowedMethods) {

            if (substr($allowedService, 0, 7) === 'regexp:') {
                if (!preg_match(substr($allowedService, 7), $service)) {
                    continue;
                }
            }
            elseif ($allowedService !== $service) {
                continue;
            }

            // if we are here, the service is matching, now check the method
            foreach($allowedMethods as $allowedMethod) {
                if (substr($allowedMethod, 0, 7) === 'regexp:') {
                    if (preg_match(substr($allowedMethod, 7), $method)) {
                        return true;
                    }
                }
                elseif ($allowedMethod === $method) {
                    return true;
                }

            }
        }

        return false;
    }
}