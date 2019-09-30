<?php

require_once(__DIR__.'/RabbitMQTest.php');

class MessagesTest extends RabbitMQTest
{
    // maximum number of seconds to wait for the queue when consuming
    protected $timeout = 5;
    protected $fabricOk = false;

    protected function setUp()
    {
        parent::setUp();

        if (! $this->fabricOk) {
            $partsHolder = $this->getContainer()->get('old_sound_rabbit_mq.parts_holder');
            foreach ($partsHolder->getParts('old_sound_rabbit_mq.base_amqp') as $baseAmqp) {
                $baseAmqp->setupFabric();
            }

            $this->fabricOk = true;
        }
    }

    public function testSendAndReceiveMessage()
    {
        $this->purgeQueues('travis_test');

        $msgProducer = $this->getMsgProducer('test_alias.kaliop_queueing.message_producer.generic_message', 'travis_test');
        $msgProducer->publish('{"hello":"world"}');
        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $accumulator->reset();
        $this->getConsumer('travis_test')->consume(1, $this->timeout);
        $this->assertContains('world', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRouting()
    {
        $this->purgeQueues('travis_test_hellodotworld', 'travis_test_bonjourdotmonde');

        $msgProducer = $this->getMsgProducer('test_alias.kaliop_queueing.message_producer.generic_message', 'travis_test');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');

        $accumulator->reset();
        $this->getConsumer('travis_test_hellodotworld')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $accumulator->reset();
        $this->getConsumer('travis_test_bonjourdotmonde')->consume(1, $this->timeout);
        $this->assertContains('fre', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingWildcard()
    {
        $this->purgeQueues('travis_test_hellodotstar', 'travis_test_stardotworld');

        $msgProducer = $this->getMsgProducer('test_alias.kaliop_queueing.message_producer.generic_message', 'travis_test');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');

        $accumulator->reset();
        $this->getConsumer('travis_test_hellodotstar')->consume(2, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult(1));

        $accumulator->reset();
        $this->getConsumer('travis_test_stardotworld')->consume(2, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult(1));
    }

    public function testSendAndReceiveMessageWithRoutingHash()
    {
        $this->purgeQueues('travis_test_hellodothash', 'travis_test_hashdotworld', 'travis_test');

        $msgProducer = $this->getMsgProducer('test_alias.kaliop_queueing.message_producer.generic_message', 'travis_test');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');

        $accumulator->reset();
        $this->getConsumer('travis_test_hellodothash')->consume(3, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult(2));

        $accumulator->reset();
        $this->getConsumer('travis_test_hashdotworld')->consume(3, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult(2));

        // this could give us back either message, if order of delivery is not guaranteed
        $accumulator->reset();
        $this->getConsumer('travis_test')->consume(4, $this->timeout);
        $this->assertThat(
            $accumulator->getConsumptionResult(3),
            $this->logicalOr(
                $this->contains('eng'),
                $this->contains('fre')
            )
        );
        $this->assertEquals(4, $accumulator->countConsumptionResult());
    }
}
