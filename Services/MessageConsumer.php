<?php

namespace Kaliop\QueueingBundle\Services;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Kaliop\QueueingBundle\Events\EventsList;
use Kaliop\QueueingBundle\Events\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for message consumers
 */
abstract class MessageConsumer implements ConsumerInterface
{
    protected $assumedContentType = null;
    // NB: if you change this value in subclasses, take care about the security implications
    /// @see self::decodeMessageBody
    protected $acceptedContentTypes = array(
        'application/json',
    );
    // we do not specify a type for this, as we could have different messages depending on transport (one day)
    protected $message;
    /** @var  \Psr\Log\LoggerInterface $logger */
    protected $logger;
    protected $dispatcher;

    /**
     * The method to be implemented by subclasses, executed upon reception of a message.
     * It can throw any exception, as those are caught anyway.
     * It should *not* leak memory ;-)
     *
     * @param mixed $data this is automatically decoded from the received message into a php data structure
     * @return void
     */
    abstract public function consume( $data );

    public function setLogger( LoggerInterface $logger=null )
    {
        $this->logger = $logger;
    }

    public function setDispatcher( EventDispatcherInterface $dispatcher=null )
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Use it f.e. in subclasses to accept other serialization methods for the received messages.
     * Only add 'application/x-httpd-php-source' or 'vnd.php.serialized' if you *really* trust the source!
     * @see decodeMessageBody for those which can be natively decoded
     *
     * @param array[string] $types
     */
    protected function setAcceptedContentTypes( $types )
    {
        $this->acceptedContentTypes = $types;
    }

    /**
     * Sets the content type which is assumed when the incoming message does not specify any
     * @see decodeMessageBody for those which can be natively decoded
     *
     * @param $type
     * @throws \InvalidArgumentException
     */
    protected function setAssumedContentType( $type )
    {
        if( !in_array( $type, $this->acceptedContentTypes ) )
        {
            throw new \InvalidArgumentException( "Content type '$type' is not accepted, so it can not be assumed" );
        }
        $this->assumedContentType = $type;
    }

    /**
     * @param AMQPMessage $msg
     *
     * @todo validate message format
     */
    public function execute( AMQPMessage $msg )
    {
        try
        {
            // save the message, in case child class needs it for whacky stuff
            $this->message = $msg;
            $body = $this->decodeMessageBody( $msg );

            // while at it, emit a message, and allow listeners to prevent further execution
            if ($this->dispatcher) {
                $event = new MessageReceivedEvent( $body, $msg, $this );
                if ($this->dispatcher->dispatch( EventsList::MESSAGE_RECEIVED, $event)->isPropagationStopped()) {
                    return;
                }
            }

            $this->consume( $body );
        }
        catch( \Exception $e )
        {
            // we keep on working, but log an error
            if ( $this->logger )
            {
                $this->logger->error( 'Unexpected exception trying to decode and consume message: ' . $e->getMessage() );
            }
        }
    }

    /**
     * Works on the basis of the assumed and accepted content types
     * @param AMQPMessage $msg
     * @return mixed
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    protected function decodeMessageBody( AMQPMessage $msg )
    {
        $properties = $msg->get_properties();
        // do we accept this type? (nb: this is an optional property)
        $type = @$properties['content_type'];
        if ( $type == '' && $this->assumedContentType != '' )
        {
            $type = $this->assumedContentType;
        }
        if ( $type == '' || !in_array( $type, $this->acceptedContentTypes ) )
        {
            throw new \RuntimeException( "Can not decode message with content type: '$type'" );
        }

        // then decode it
        switch( $properties['content_type'] )
        {
            case 'application/json':
                $data = json_decode( $msg->body, true );
                if ( $error = json_last_error() )
                {
                    throw new \UnexpectedValueException( "Error decoding json payload: " . $error );
                }
                return $data;
             case 'application/x-httpd-php-source':
                /// @todo should we wrap this in try/catch, ob_start and set_error_handler, or just make sure it is never used?
                return eval ( 'return ' . $msg->body . ';' );
            case 'vnd.php.serialized':
                return unserialize( $msg->body );
            case 'text/plain':
            case 'application/octet-stream':
                return $msg->body;
            default:
                throw new \UnexpectedValueException( "Serialization format unsupported: " . $type );
        }
    }
}