<?php

namespace Kaliop\QueueingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Sniffs out the consumers and producers which are dynamically created by the OldSound bundle, and registers their ids
 */
class RegisterOldSoundPass implements CompilerPassInterface
{
    protected $queueManagerService;

    public function __construct(
        $queueManagerService = 'kaliop_queueing.amqp.queue_manager'
    )
    {
        $this->queueManagerService = $queueManagerService;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->queueManagerService) && !$container->hasAlias($this->queueManagerService)) {
            return;
        }

        $definition = $container->findDefinition($this->queueManagerService);

        foreach($container->findTaggedServiceIds('old_sound_rabbit_mq.producer') as $id => $val) {
            $id = preg_replace(array('/^old_sound_rabbit_mq\./', '/_producer$/'), '', $id);
            $definition->addMethodCall('registerProducer', array($id));
        }
        foreach($container->findTaggedServiceIds('old_sound_rabbit_mq.consumer') as $id => $val) {
            $id = preg_replace(array('/^old_sound_rabbit_mq\./', '/_consumer$/'), '', $id);
            $definition->addMethodCall('registerConsumer', array($id));
        }
    }
}