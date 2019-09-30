<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class sampleTest extends WebTestCase
{
    protected function getContainer()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
        return static::$kernel->getContainer();
    }

    /**
     * Minimalistic test: check that all known services can be loaded
     */
    public function testKnownServices()
    {
        $container = $this->getContainer();
        $service = $container->get('kaliop_queueing.drivermanager');
        $service = $container->get('kaliop_queueing.driver.rabbitmq');
        $service = $container->get('test_alias.kaliop_queueing.event_dispatcher');
        $service = $container->get('kaliop_queueing.worker_manager');
        $service = $container->get('kaliop_queueing.watchdog');
        $service = $container->get('test_alias.kaliop_queueing.amqp.queue_manager');
        $service = $container->get('kaliop_queueing.message_producer.console_command');
        $service = $container->get('test_alias.kaliop_queueing.message_consumer.console_command');
        $service = $container->get('test_alias.kaliop_queueing.message_producer.symfony_service');
        $service = $container->get('test_alias.kaliop_queueing.message_consumer.symfony_service');
        $service = $container->get('test_alias.kaliop_queueing.message_producer.http_request');
        $service = $container->get('test_alias.kaliop_queueing.message_consumer.http_request');
        $service = $container->get('test_alias.kaliop_queueing.message_producer.xmlrpc_call');
        $service = $container->get('test_alias.kaliop_queueing.message_consumer.xmlrpc_call');
        $service = $container->get('test_alias.kaliop_queueing.message_producer.generic_message');
        $service = $container->get('test_alias.kaliop_queueing.message_consumer.noop');
        $service = $container->get('kaliop_queueing.message_consumer.console_command.filter');
        $service = $container->get('kaliop_queueing.message_consumer.symfony_service.filter');
        $service = $container->get('kaliop_queueing.message_consumer.http_request.filter');
        $service = $container->get('kaliop_queueing.message_consumer.filter.monitor');
        $service = $container->get('kaliop_queueing.message_consumer.filter.stopwatch');
        $service = $container->get('kaliop_queueing.message_consumer.filter.accumulator');

        // useless assertion used to silence a warning that this test is risky
        $this->assertEquals(1, 1);
    }
}
