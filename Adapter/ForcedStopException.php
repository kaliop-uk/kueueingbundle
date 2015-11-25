<?php

namespace Kaliop\QueueingBundle\Adapter;

/**
 * Can be thrown by an adapter when the Consumer has received a forceStopConsumer() call, as a way of exiting consumption
 */
class ForcedStopException extends \Exception
{

}
