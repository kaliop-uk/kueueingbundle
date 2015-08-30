<?php

namespace Kaliop\QueueingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Kaliop\QueueingBundle\DependencyInjection\Compiler\RegisterDriversPass;
use Kaliop\QueueingBundle\DependencyInjection\Compiler\RegisterListenersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KaliopQueueingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterDriversPass());
        $container->addCompilerPass(new RegisterListenersPass());
    }
}