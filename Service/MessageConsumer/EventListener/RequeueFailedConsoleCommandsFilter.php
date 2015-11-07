<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageConsumedEvent;
use Kaliop\QueueingBundle\Service\MessageProducer\ConsoleCommand;

/**
 * An event listener which can be used to requeue consolecommand execution messages, when the execution of the
 * command fails.
 * It is to be set up as listener for queues where the consumer is a ConsoleCommand.
 * The queue where the failed commands are requeued to is defined by passing to the constructor a fully configured messageproducer service.
 */
class RequeueFailedConsoleCommandsFilter
{
    protected $messageProducer;
    protected $key;
    protected $ttl;

    /**
     * @param ConsoleCommand $messageProducer a fully configured messageproducer service (with the good driver and queue set)
     * @param string $key if null, will be calculated based on the command
     * @param int $ttl seconds
     */
    public function __construct(ConsoleCommand $messageProducer, $key=null, $ttl = null)
    {
        $this->messageProducer = $messageProducer;
    }

    public function onMessageConsumed(MessageConsumedEvent $event)
    {
        $results = $event->getConsumptionResult();
        if (!is_array($results) || count($results) != 3) {
            // this consumption result is not the outptut of a consolecommand consumer!
            return;
        }

        $retCode = $results[0];

        if ($retCode != 0) {
            $body = $event->getBody();
            $this->messageProducer->publish(
                $body['command'],
                isset($body['arguments']) ? $body['arguments'] : array(),
                isset($body['options']) ? $body['options'] : array(),
                $this->key,
                $this->ttl
            );
        }
    }
}