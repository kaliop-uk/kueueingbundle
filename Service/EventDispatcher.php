<?php

namespace Kaliop\QueueingBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;

class EventDispatcher extends ContainerAwareEventDispatcher
{
    // parent member is private, so we set up a new copy for our own use
    private $listenerIds = array();
    // same...
    private $container;
    // same...
    private $listeners = array();

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
    }

    /**
     * Adds a service as event listener.
     *
     * @param string $eventName Event for which the listener is added
     * @param array  $callback  The service ID of the listener service & the method
     *                          name that has to be called
     * @param int    $priority  The higher this value, the earlier an event listener
     *                          will be triggered in the chain.
     *                          Defaults to 0.
     * @param string $queueName Use null to subscribe to all queues
     * @throws \InvalidArgumentException
     */
    public function addListenerService($eventName, $callback, $priority = 0, $queueName = null)
    {
        parent::addListenerService($eventName, $callback, $priority);

        $this->listenerIds[$eventName][] = array($callback[0], $callback[1], $priority, $queueName);
    }

    /**
     * Triggers the listeners of an event UNLESS they are only listening to a different queue.
     * Since we get an array of listener instances which are not tied any more to the service key, we have to do a slow
     * loop to find if any listener was tied to a particular queue...
     *
     * @param callable[] $listeners The event listeners.
     * @param string     $eventName The name of the event to dispatch.
     * @param Event      $event     The event object to pass to the event handlers/listeners.
     */
    protected function doDispatch($listeners, $eventName, Event $event)
    {
        foreach ($listeners as $id => $listener) {

            if (isset($this->listeners[$eventName])) {
                foreach($this->listeners[$eventName] as $key => $val) {
                    // services
                    if ($val[0] === $listener[0]) {
                        // queue names
                        if ($val[1] != null && $val[1] != $event->getMessage()->getQueueName()) {
                            continue 2;
                        }
                        break;
                    }
                }
            }

            call_user_func($listener, $event, $eventName, $this);
            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }

    /**
     * When loading listener services, store in a separate index the queue to which service is limited
     * @param string $eventName
     */
    protected function lazyLoad($eventName)
    {
        parent::lazyLoad($eventName);

        if (isset($this->listenerIds[$eventName])) {
            foreach ($this->listenerIds[$eventName] as $args) {
                list($serviceId, $method, $priority, $queueName) = $args;
                if ($queueName != null) {
                    $listener = $this->container->get($serviceId);
                    $key = $serviceId.'.'.$method;
                    $this->listeners[$eventName][$key] = array($listener, $queueName);
                }
            }
        }
    }
}
