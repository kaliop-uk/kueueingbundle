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
     * The MESSAGE_CONSUMED event is thrown each time a message is received, after it is processed.
     * NB: if a listener for the MESSAGE_RECEIVED event cancels the consuming, this event will not be fired.
     * NB: if an exception is thrown by the code processing the message, this event will not be fired
     * The event listener receives a MessageConsumedEvent instance.
     */
    const MESSAGE_CONSUMED = 'kaliop_queueing.message_consumed';

    /**
     * The MESSAGE_CONSUMED event is thrown each time a message is received, when an exception is thrown while it is processed.
     * NB: if a listener for the MESSAGE_RECEIVED event cancels the consuming, this event will not be fired.
     * The event listener receives a MessageConsumptionFailedEvent instance.
     */
    const MESSAGE_CONSUMPTION_FAILED = 'kaliop_queueing.message_consumption_failed';

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