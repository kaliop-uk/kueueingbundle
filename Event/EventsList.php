<?php

namespace Kaliop\QueueingBundle\Event;

final class EventsList
{
    /**
     * The MESSAGE_RECEIVED event is thrown each time a message is received, before it is processed
     * The event listener receives a MessageReceivedEvent instance.
     */
    const MESSAGE_RECEIVED = 'kaliop_queueing.message_received';

    /**
     * The PROCESS_STARTED event is thrown each time the watchdog starts a process (just after)
     * The event listener receives a ProcessStartedEvent instance.
     */
    const PROCESS_STARTED = 'kaliop_queueing.process_started';

    /**
     * The PROCESS_STOPPED event is thrown each time the watchdog stops a process (just after)
     * The event listener receives a ProcessStoppedEvent instance.
     */
    const PROCESS_STOPPED = 'kaliop_queueing.process_stopped';
}