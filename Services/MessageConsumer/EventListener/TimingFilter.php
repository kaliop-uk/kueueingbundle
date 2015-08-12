<?php

namespace Kaliop\QueueingBundle\Services\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Events\MessageReceivedEvent;

/**
 * A class which can be registered as listener in order to time the speed of message consumption:
 * it will display the passed time every N received messages
 *
 * @todo inject the Sf logger and use it for output instead of plain echo?
 */
class TimingFilter
{
    protected $count;
    protected $received = 0;
    protected $started = 0;

    /**
     * @param int $count the number of received messages to measure the time for
     */
    public function __construct($count)
    {
        $this->count = $count;
    }

    public function onMessageReceived(MessageReceivedEvent $event)
    {
        if ($this->received === 0) {
            $this->started = microtime(true);
        }
        $this->received++;
        if ($this->received === $this->count) {
            $elapsed = microtime(true) - $this->started;
            printf("Time spent to receive {$this->count} messages: %.3f secs (%.3f m/s)\n", $elapsed, $this->count / $elapsed );
            $this->received = 0;
        }
    }
}