<?php

namespace Kaliop\QueueingBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * A helper class, exposed as service.
 *
 * All it does is to
 * - allow injection of the name of the producer via service configuration, allowing subclasses to be more nimble
 * - define a standard serialization format: json (and support 2 more)
 *
 * @todo it would be nice if we could force subclasses to implement a way to document their message format using e.g. jsonschema
 */
abstract class MessageProducer extends ContainerAware
{
    protected $queue = null;
    protected $contentType = 'application/json';
    protected static $knownContentTypes = array(
        'application/json',
        'application/x-httpd-php-source',
        'vnd.php.serialized'
    );

    /**
     * NB: when used for RabbitMQ, the queue name is the name of the producer as defined in old_sound_rabbit_mq.producers,
     *     it is not the name of the actual amqp queue
     * @param string $queue
     * @throws \UnexpectedValueException
     */
    public function setQueueName( $queue )
    {
        if ( $queue == '' )
        {
            throw new \UnexpectedValueException( "Queue name can not be empty" );
        }
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queue;
    }

    /**
     * @return \OldSound\RabbitMqBundle\RabbitMq\Producer
     */
    protected function getProducerService()
    {
        return $this->container->get( 'old_sound_rabbit_mq.' . $this->getQueueName() .'_producer' );
    }

    /**
     * Returns the content type which will be used to serialize messages
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Sets the content type which will be used to serialize messages. Fails if type is unsupported
     * @param string $type
     * @throws \InvalidArgumentException
     */
    public function setContentType( $type )
    {
        if ( !in_array( $type, static::$knownContentTypes ) )
        {
            throw new \InvalidArgumentException( "Content type '$type' is not supported" );
        }
        $this->contentType = $type;
    }

    /**
     * Encodes data according to current content type.
     * If you reimplement this in a subclass, do not forget to also add new types to self::$knownContentTypes
     * @param mixed $data
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function encodeMessageBody( $data )
    {
        switch( $this->contentType )
        {
            case 'application/json':
                return json_encode( $data );
            case 'application/x-httpd-php-source':
                return var_export( $data, true );
            case 'vnd.php.serialized':
                return serialize( $data );
            default:
                throw new \UnexpectedValueException( "Serialization format unsupported: " . $this->contentType );
        }
    }

    /**
     * Some sugar for subclasses
     *
     * NB: the "extras" parameter only works as long as our customized class is used instead of Kaliop\QueueingBundle\RabbitMq\Producer
     * (this happens naturally when this bundle is properly configured, as it is out of the box)
     *
     * @param mixed $data
     * @param string $routingKey
     * @param array $extras
     */
    protected function doPublish( $data, $routingKey = '', $extras = array() )
    {
        $this->getProducerService()
            ->setContentType( $this->getContentType() )
            ->publish( $this->encodeMessageBody( $data ), $routingKey, $extras );
    }

}