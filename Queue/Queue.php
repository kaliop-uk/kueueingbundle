<?php

namespace Kaliop\QueueingBundle\Queue;

abstract class Queue
{
    const TYPE_PRODUCER = 1;
    const TYPE_CONSUMER = 2;
    const TYPE_ANY = 3;
}
