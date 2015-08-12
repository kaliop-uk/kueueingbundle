<?php
/**
 * User: gaetano.giunta
 * Date: 02/05/14
 * Time: 13.01
 */

/******************************************************************************
 *
 * *** BIG FAT SECURITY WARNING ***
 *
 * This is in short a remote-code-execution attack.
 * NEVER expose it unless you absolutely trust the senders
 *
 *****************************************************************************/

namespace Kaliop\QueueingBundle\Services\MessageConsumer;

use Kaliop\QueueingBundle\Services\MessageConsumer;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Allows execution of arbitrary method calls on Sf services from queue messages
 *
 * *** SECURITY WARNING ***
 * NEVER expose this unless you absolutely trust the senders
 */
class SymfonyService extends MessageConsumer
{
    protected $container;

    public function __construct( Container $container )
    {
        $this->container = $container;
    }

    public function consume( $body )
    {
        // validate members in $body
        if (
            !is_array( $body ) ||
            empty( $body['service'] ) ||
            empty( $body['method'] ) ||
            ( isset( $body['arguments'] ) && !is_array( $body['arguments'] ) )
        )
        {
            throw new \UnexpectedValueException( "Message format unsupported: missing 'service' or 'method' or invalid 'arguments'" );
        }

        // for a speed/resource gain, we test: if service is not registered, do not try to run it
        $this->validateService( $body['service'], $body['method'], @$body['arguments'] );

        $this->runService( $body['service'], $body['method'], @$body['arguments'] );
    }

    /**
     * Throws an error if service is not declared or methodName does not apply
     *
     * @param string $serviceName
     * @param string $methodName
     * @param array $arguments
     * @throws
     */
    protected function validateService( $serviceName, $methodName, $arguments = array() )
    {
        $service = $this->container->get( $serviceName );
        if ( !is_callable( array( $service, $methodName ) ) )
        {
            throw new \UnexpectedValueException( "Method $methodName not found in class " . get_class( $service ) . " implementing service $serviceName" );
        }
    }

    protected function runService( $serviceName, $methodName, $arguments = array() )
    {
        $service = $this->container->get( $serviceName );
        return call_user_func_array( array( $service, $methodName ), $arguments );
    }
}