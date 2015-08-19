<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer;

use Kaliop\QueueingBundle\Service\MessageConsumer;
use Kaliop\QueueingBundle\Command\ConsumerCommand;
use PhpXmlRpc\Client as XC;
use PhpXmlRpc\Request as XR;
use PhpXmlRpc\Encoder as XE;

/**
 * This service can be registered to consume any kind messages. It does absolutely nothing with them.
 * It can be used for testing, timing etc...
 */
class Noop extends MessageConsumer
{
    public function consume( $body )
    {
    }
}