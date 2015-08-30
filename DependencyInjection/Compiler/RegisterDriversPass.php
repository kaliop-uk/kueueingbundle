<?php

namespace Kaliop\QueueingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @see Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass
 *
 * @todo add support for Subscribers (see commented code)
 */
class RegisterDriversPass implements CompilerPassInterface
{
    protected $driverManagerService;
    protected $serviceTag;

    public function __construct($driverManagerService='kaliop_queueing.drivermanager', $serviceTag = 'kaliop_queueing.driver')
    {
        $this->driverManagerService = $driverManagerService;
        $this->serviceTag = $serviceTag;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->driverManagerService) && !$container->hasAlias($this->driverManagerService)) {
            return;
        }
        $definition = $container->findDefinition($this->driverManagerService);

        $taggedDrivers = array();
        foreach ($container->findTaggedServiceIds($this->serviceTag) as $id => $defs) {
            foreach ($defs as $def) {
                if (!isset($def['alias'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "alias" attribute on "%s" tags.', $id, $this->serviceTag));
                }
                $taggedDrivers[$def['alias']] = $id;
                $definition->addMethodCall('registerDriver', array($def['alias'], $id));
            }
        }
    }
}