<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer;

use Kaliop\QueueingBundle\Service\MessageConsumer;
use Kaliop\QueueingBundle\Command\ConsumerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\Process;

/**
 * This service can be registered to consume "send http requests" messages
 */
class HTTPRequest extends MessageConsumer
{
    /**
     * @param array $body
     * @throws \UnexpectedValueException
     */
    public function consume($body)
    {
        // validate members in $body
        if (
            !is_array($body) ||
            empty($body['url']) ||
            (isset($body['options']) && !is_array($body['options']))
        ) {
            throw new \UnexpectedValueException("Message format unsupported: missing 'url'? Received: " . json_encode($body));
        }

        if (!isset($body['options'])) {
            $body['options'] = array();
        }

        $this->executeRequest($body['url'], $body['options']);
    }

    /**
     * Runs an sf command as a separate php process - this way we insure the worker is stable (no memleaks or crashes)
     *
     * @param string $url
     * @param array $options
     * @return string|false
     */
    protected function executeRequest($url, array $options = array())
    {
        $ch = curl_init($url);

        foreach ($options as $name => $value) {
            if (!curl_setopt($ch, $name, $value)) {
                if ($this->logger) {
                    $this->logger->warning("Option could not be set to Curl: $name, value: $value");
                }
            }
        }

        // some options we have to force
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $results = curl_exec($ch);
        if ($results === false) {
            if ($this->logger) {
                $this->logger->error("Curl request failed: " . curl_error($ch));
            }
        }

        // close cURL resource, and free up system resources
        curl_close($ch);

        return $results;
    }
}