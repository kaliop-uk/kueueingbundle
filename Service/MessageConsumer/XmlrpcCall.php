<?php

namespace Kaliop\QueueingBundle\Service\MessageConsumer;

use Kaliop\QueueingBundle\Service\MessageConsumer;
use Kaliop\QueueingBundle\Command\ConsumerCommand;
use PhpXmlRpc\Client as XC;
use PhpXmlRpc\Request as XR;
use PhpXmlRpc\Encoder as XE;

/**
 * This service can be registered to consume "send xmlrpc requests" messages
 */
class XmlrpcCall extends MessageConsumer
{
    /**
     * @todo add support for options to be set to the xmlrpc client
     * @todo on the other hand, the list of allowed xmlrpc server with options might be stored in settings, just like ggwebservices does...
     *
     * @param array $body
     * @return mixed
     * @throws \UnexpectedValueException
     */
    public function consume($body)
    {
        // validate members in $body
        if (
            !is_array($body) ||
            empty($body['server']) ||
            empty($body['method']) ||
            (isset($body['arguments']) && !is_array($body['arguments'])) /*||
            ( isset( $body['options'] ) && !is_array( $body['options'] ) ) */
        ) {
            throw new \UnexpectedValueException("Message format unsupported. Received: " . json_encode($body));
        }

        $label = trim(ConsumerCommand::getLabel());
        if ($label != '') {
            $label = " '$label'";
        }

        if ($this->logger) {
            $this->logger->debug("XMLRPC call will be executed from MessageConsumer{$label}: " . $body['method'] . " on server: " . $body['server']);
        }

        $encoder = new XE();
        $args = array();
        foreach ($body['arguments'] as $val) {
            $args[] = $encoder->encode($val);
        }
        $client = new XC($body['server']);
        $response = $client->send(new XR($body['method'], $args));

        if ($response->faultCode() != 0 && $this->logger) {
            $this->logger->error(
                "XMLRPC call executed from MessageConsumer{$label} failed. Retcode: " . $response->faultCode() . ", Error message: '" . $response->faultString() . "'",
                array());
        }

        return $response->faultCode() == 0 ? $encoder->decode($response->value()) : null;
    }
}
