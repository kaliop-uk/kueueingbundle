<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer\EventListener;

use Kaliop\QueueingBundle\Event\MessageReceivedEvent;

/**
 * A class which can be registered as listener in order to filter ConsoleCommand messages
 */
class ConsoleCommandFilter
{
    protected $allowedCommands;

    public function __construct(array $allowedCommands)
    {
        $this->allowedCommands = $allowedCommands;
    }

    public function onMessageReceived(MessageReceivedEvent $event)
    {
        // filter out unwanted events
        if (!$event->getConsumer() instanceof \Kaliop\QueueingBundle\Service\MessageConsumer\ConsoleCommand)
            return;

        $body = $event->getBody();
        $command = @$body['command'];
        if (empty($command)) {
            /// we leave it up to the consumer to respond to these messages...
            return;
        }

        if (!$this->isCommandAllowed($command)) {
            $event->stopPropagation();
        }
    }

    protected function isCommandAllowed($command)
    {
        foreach ($this->allowedCommands as $allowedCommand) {
            if (substr($allowedCommand, 0, 7) === 'regexp:') {
                if (preg_match(substr($allowedCommand, 7), $command)) {
                    return true;
                }
            } elseif ($allowedCommand === $command) {
                return true;
            }
        }

        return false;
    }
}
