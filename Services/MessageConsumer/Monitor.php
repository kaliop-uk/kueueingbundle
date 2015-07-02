<?php

namespace Kaliop\QueueingBundle\Services\MessageConsumer;

use Kaliop\QueueingBundle\Services\MessageConsumer as BaseMessageConsumer;

/**
 * A braindead class which is used to consume rabbitmq messages.
 * It is hooked up via service configuration and rabbitmq configuration.
 *
 */
class Monitor extends BaseMessageConsumer
{
    public function consume( $msg )
    {
        echo "Received a message at " . strftime( '%Y/%m/%d - %H:%M:%S', time() ) . ": " . var_export( $msg, true ) . "\n";
    }
}