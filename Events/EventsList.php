<?php

namespace Kaliop\QueueingBundle\Events;

final class EventsList
{
    /**
     * The MESSAGE_RECEIVED event is thrown each time a message is received, before it is processed
     *
     * The event listener receives an
     * Acme\StoreBundle\Event\FilterOrderEvent instance.
     *
     * @var string
     */
    const MESSAGE_RECEIVED = 'kaliop_queueing.message_received';
}