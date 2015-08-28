<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageReceivedEvent;

/**
 * A class which can be registered as listener in order to measure the speed of message consumption:
 * it will display every N received messages the time taken as well as the messages/second.
 * This kind of measure makes most sense when the queue is full of messages, or at least the message producer
 * is running at its full capacity.
 *
 * @todo inject the Sf logger and use it for output instead of plain echo?
 */
class StopwatchFilter
{
    protected $count;
    protected $received = 0;
    protected $started = 0;

    /**
     * @param int $count the number of received messages to measure the time for. Should be >= 1
     */
    public function __construct($count)
    {
        $this->count = $count;
    }

    /**
     * NB: we measure the time consumed for processing of N messages upon receiving the Nth+1 message.
     * This because we have only the before-execution event available.
     */
    public function onMessageReceived(MessageReceivedEvent $event)
    {
        if ($this->received === 0) {
            $this->started = microtime(true);
        } else if ($this->received === $this->count) {
            $time = microtime(true);
            $elapsed = $time - $this->started;
            printf("Time spent to receive {$this->count} messages: %.3f secs (%.3f m/s)\n", $elapsed, $this->count / $elapsed);
            $this->started = $time;
            $this->received = 0;
        }
        $this->received++;
    }
}