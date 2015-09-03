<?php

namespace Kaliop\QueueingBundle\Queue;

/**
 * Modeled after the AMQP message:
 *
 * - body
 * - the content-type, allowing to deserialize the body
 * - a bag of properties
 */
interface MessageInterface
{

    /** @return string */
    public function getBody();

    /** @return int */
    // not needed so far
    //public function getBodySize();

    /** @var bool */
    // not needed so far
    //public $is_truncated;

    /** @return string */
    public function getContentType();

    /** @return array */
    // not needed so far
    //public function getDeliveryInfo();

    /**
     * Check whether a property exists in the 'properties' dictionary
     * ...to be determined:...  or if present - in the 'delivery_info' dictionary.
     *
     * @param string $name
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function get($name);

    /**
     * Returns the properties content
     * @return array
     */
    public function getProperties();
}