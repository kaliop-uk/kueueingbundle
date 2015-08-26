<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Queue\Queue;
use PhpAmqpLib\Message\AMQPMessage;
use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Driver extends ContainerAware implements DriverInterface
{
    public function acceptMessage($message)
    {
        return $message instanceof \PhpAmqpLib\Message\AMQPMessage;
    }

    /**
     * @param AMQPMessage $message
     * @return \Kaliop\QueueingBundle\Queue\MessageInterface
     */
    public function decodeMessage($message)
    {
        return new Message($message);
    }

    /**
     * @param $queueName
     * @return \Kaliop\QueueingBundle\Queue\MessageProducerInterface
     */
    public function getMessageProducer($queueName)
    {
        return $this->container->get('old_sound_rabbit_mq.' . $queueName .'_producer');
    }

    /**
     * This is a bit dumb, but so far all we have found is to go through all services, and check based on names:
     * @param int $type
     * @return string[]
     */
    public function listQueues($type = Queue::TYPE_ANY)
    {
        $out = array();
        foreach($this->findServiceIdsContaining('old_sound_rabbit_mq.') as $serviceName) {
            switch($type) {
                case Queue::TYPE_CONSUMER:
                    if (preg_match('/_consumer$/', $serviceName))
                        $out[] = str_replace(array('old_sound_rabbit_mq.', '_consumer'), '', $serviceName);
                    break;
                case Queue::TYPE_PRODUCER:
                    if (preg_match('/_producer$/', $serviceName))
                        $out[] = str_replace(array('old_sound_rabbit_mq.', '_producer'), '', $serviceName);
                    break;
                case Queue::TYPE_ANY:
                    if (preg_match('/_(consumer|producer)$/', $serviceName))
                        $out[] = str_replace(array('old_sound_rabbit_mq.', '_consumer', '_producer'), '', $serviceName);
            }
        }
        return $out;
    }

    private function findServiceIdsContaining($name)
    {
        $builder = $this->getContainerBuilder();
        $serviceIds = $builder->getServiceIds();
        $foundServiceIds = array();
        $name = strtolower($name);
        foreach ($serviceIds as $serviceId) {
            if (false === strpos($serviceId, $name)) {
                continue;
            }
            $foundServiceIds[] = $serviceId;
        }

        return $foundServiceIds;
    }

    protected function getContainerBuilder()
    {
        /// @todo reintroduce check
        //if (!$this->getApplication()->getKernel()->isDebug()) {
        //    throw new \LogicException(sprintf('Debug information about the container is only available in debug mode.'));
        //}

        if (!is_file($cachedFile = $this->container->getParameter('debug.container.dump'))) {
            throw new \LogicException(sprintf('Debug information about the container could not be found. Please clear the cache and try again.'));
        }

        $container = new ContainerBuilder();

        $loader = new XmlFileLoader($container, new FileLocator());
        $loader->load($cachedFile);

        return $container;
    }

}