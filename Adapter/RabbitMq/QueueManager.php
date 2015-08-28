<?php
/**
 * User: gaetano.giunta
 * Date: 19/05/14
 * Time: 19.08
 */

namespace Kaliop\QueueingBundle\Adapter\RabbitMq;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use InvalidArgumentException;
use Kaliop\QueueingBundle\Queue\QueueManagerInterface;

/**
 * A class dedicated not really to sending messages to a queue, bur rather to sending control commands
 *
 * @todo add a new action: listing available queues (i.e. defined in config)
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
        return array('purge', 'delete', 'info');
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