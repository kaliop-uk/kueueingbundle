<?php

namespace Kaliop\QueueingBundle\Queue;

interface SignalHandlingConsumerInterface
{
    /**
     * This method will be called by the consumer command, to tell the consumer whether it should handle signals.
     * Signals are used to allow graceful shutdowns with loss of messages.
     * When a consumer has to handle signals, it is supposed to:
     * - call pcntl_signal_dispatch in its msg consuming loop
     * - handle a graceful exit when forceStop is called (i.e. without loosing messages already downloaded from the queue)
     *
     * @param bool $doHandle
     * @return $this
     */
    public function setHandleSignals($doHandle);

    /**
     * @param string $reason
     */
    public function forceStop($reason = '');
}