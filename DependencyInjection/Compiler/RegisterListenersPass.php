<?php

namespace Kaliop\QueueingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @see Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass
 *
 * @todo add support for Subscribers (see commented code)
 */
class RegisterListenersPass implements CompilerPassInterface
{
    protected $listenerTag;
    protected $dispatcherService;
    protected $subscriberTag;

    public function __construct(
        $dispatcherService = 'kaliop_queueing.event_dispatcher',
        $listenerTag = 'kaliop_queueing.event_listener',
        $subscriberTag = 'kaliop_queueing.event_subscriber'
    )
    {
        $this->dispatcherService = $dispatcherService;
        $this->listenerTag = $listenerTag;
        $this->subscriberTag = $subscriberTag;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->dispatcherService) && !$container->hasAlias($this->dispatcherService)) {
            return;
        }

        $definition = $container->findDefinition($this->dispatcherService);

        foreach ($container->findTaggedServiceIds($this->listenerTag) as $id => $events) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event listeners are lazy-loaded.', $id));
            }

            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event listeners are lazy-loaded.', $id));
            }

            foreach ($events as $event) {
                $priority = isset($event['priority']) ? $event['priority'] : 0;

                if (!isset($event['event'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "event" attribute on "%s" tags.', $id, $this->listenerTag));
                }

                if (!isset($event['method'])) {
                    // this is the only change compared to Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass...
                    // could we just subclass that one instead of having a dedicated class?
                    $method = str_replace( 'kaliop_queueing.', '', $event['event'] );
                    $method = 'on'.preg_replace_callback(array(
                            '/(?<=\b)[a-z]/i',
                            '/[^a-z0-9]/i',
                        ), function ($matches) { return strtoupper($matches[0]); }, $method);
                    $event['method'] = preg_replace('/[^a-z0-9]/i', '', $method);
                }
                $definition->addMethodCall('addListenerService', array($event['event'], array($id, $event['method']), $priority));
            }
        }

        /*
        foreach ($container->findTaggedServiceIds($this->subscriberTag) as $id => $attributes) {
            $def = $container->getDefinition($id);
            if (!$def->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as event subscribers are lazy-loaded.', $id));
            }

            if ($def->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as event subscribers are lazy-loaded.', $id));
            }

            // We must assume that the class value has been correctly filled, even if the service is created by a factory
            $class = $container->getParameterBag()->resolveValue($def->getClass());

            $refClass = new \ReflectionClass($class);
            $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
            if (!$refClass->implementsInterface($interface)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
            }

            $definition->addMethodCall('addSubscriberService', array($id, $class));
        }*/
    }
}