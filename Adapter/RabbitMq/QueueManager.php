<?php

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use InvalidArgumentException;
use Kaliop\QueueingBundle\Queue\Queue;
use Kaliop\QueueingBundle\Queue\QueueManagerInterface;

/**
 * A class dedicated not really to sending messages to a queue, bur rather to sending control commands
 */
class QueueManager extends BaseMessageProducer implements ContainerAwareInterface, QueueManagerInterface
{

    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function listActions()
    {
        return array('purge', 'delete', 'info', 'list');
    }

    /**
     * Reimplemented to avoid throw on empty queue name
     * @param string $queue
     * @return QueueManager
     */
    public function setQueueName($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    public function executeAction($action)
    {
        switch ($action) {
            case 'purge':
                return $this->purgeQueue();

            case 'delete':
                return $this->deleteQueue();

            case 'info':
                return $this->queueInfo();

            case 'list':
                return $this->listQueues();

            default:
                throw new InvalidArgumentException("Action $action not supported");
        }
    }

    protected function purgeQueue()
    {
        $channelService = $this->getProducerService();
        $channel = $channelService->getChannel();
        // At this point, we need a handle on the queueOptions of the $channelService, but they are protected!
        // Luckily we are pesky little critters, and used the DIC to subclass it [grin]
        $queueOptions = $channelService->getQueueOptions();
        $rabbitQueue = $queueOptions['name'];
        if ($rabbitQueue == '') {
            // what to do here ?
        }
        return $channel->queue_purge($rabbitQueue);
    }

    /**
     * @todo add parameters: ifempty, ifunused
     */
    protected function deleteQueue()
    {
        $channelService = $this->getProducerService();
        $channel = $channelService->getChannel();
        // At this point, we need a handle on the queueOptions of the $channelService, but they are protected!
        // Luckily we are pesky little critters, and used the DIC to subclass it [grin]
        $queueOptions = $channelService->getQueueOptions();
        $rabbitQueue = $queueOptions['name'];
        if ($rabbitQueue == '') {
            // what to do here ?
        }
        return $channel->queue_delete($rabbitQueue);
    }

    protected function queueInfo()
    {
        $channelService = $this->getProducerService();
        // we need to set up the fabric to get basic infos
        $channelService->setupFabric();
        $channel = $channelService->getChannel();
        // At this point, we need a handle on the queueOptions of the $channelService, but they are protected!
        // Luckily we are pesky little critters, and used the DIC to subclass it [grin]
        $queueOptions = $channelService->getQueueOptions();
        $rabbitQueue = $queueOptions['name'];
        if ($rabbitQueue == '') {
            // what to do here ?
        }
        return array(
            'queue_stats' => $channelService->getQueueStats(),
            'queue_options' => $queueOptions,
            'exchange_options' => $channelService->getExchangeOptions(),
        );
    }

    /**
     * Returns (if supported) an array of queues configured in the application.
     * NB: these are the names of queues as seen by the app
     * - NOT the queues available on the broker
     * - NOT using the queues names used by the broker (unless those are always identical to the names used by the app)
     *
     * It is a bit dumb, but so far all we have found is to go through all services, and check based on names:
     *
     * @param int $type
     * @return string[] index is queue name, value is queue type
     */
    public function listQueues($type = Queue::TYPE_ANY)
    {
        $out = array();
        foreach ($this->findServiceIdsContaining('old_sound_rabbit_mq.') as $serviceName) {
            switch ($type) {
                case Queue::TYPE_CONSUMER:
                    if (preg_match('/_consumer$/', $serviceName))
                        $out[str_replace(array('old_sound_rabbit_mq.', '_consumer'), '', $serviceName)] = Queue::TYPE_CONSUMER;
                    break;
                case Queue::TYPE_PRODUCER:
                    if (preg_match('/_producer$/', $serviceName))
                        $out[str_replace(array('old_sound_rabbit_mq.', '_producer'), '', $serviceName)] = Queue::TYPE_PRODUCER;
                    break;
                case Queue::TYPE_ANY:
                    if (preg_match('/_consumer$/', $serviceName))
                        $out[str_replace(array('old_sound_rabbit_mq.', '_consumer'), '', $serviceName)] = Queue::TYPE_CONSUMER;
                    if (preg_match('/_producer$/', $serviceName))
                        $out[str_replace(array('old_sound_rabbit_mq.', '_producer'), '', $serviceName)] = Queue::TYPE_PRODUCER;
            }
        }
        return $out;
    }

    protected function findServiceIdsContaining($name)
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

    /**
     * @return ContainerBuilder
     */
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

    /**
     * Hack: generally queues are defined consumer-side, so we try that 1st and producer-side 2nd (but that only gives
     * us channel usually).
     * Note also that we bypass the driver here, as this message producer is quite specific
     */
    protected function getProducerService()
    {
        try {
            // nopes... these are not parameters
            //var_dump( $this->container->getParameter( 'old_sound_rabbit_mq.consumers' ) ); //. $this->getQueueName() .'.queue_options.name' ) );
            return $this->container->get('old_sound_rabbit_mq.' . $this->getQueueName() . '_consumer');
        } catch (ServiceNotFoundException $e) {
            return $this->container->get('old_sound_rabbit_mq.' . $this->getQueueName() . '_producer');
        }
    }
}