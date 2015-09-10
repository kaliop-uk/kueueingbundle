# Kaliop Queueing Bundle

A Symfony Bundle offering functionality related to message queuing systems.

Main use cases:

- make it easy to write message producers and consumers
- shield the rest of the application from the messaging system in use
- make it easy to introduce a queueing system in an existing application, allowing remote execution of existing console commands/services/etc
- help with the creation of job-processing message consumers which work as daemons, in php, overcoming the inherent
  stability problems.

As of now the only messaging broker supported is RabbitMQ via the RabbitMqBundle; other brokers supporting the AMQP
protocol (version 0.9) are likely to work but untested.

Support for other messaging systems is available in separate bundles:

- AWS Kinesis
- AWS SQS


## Features implemented

* A MessageProducer class which can be used to distribute execution of any Symfony console command to distributed workers

* A console command which can be used to test the above (distribute execution of existing console commands)

* A MessageConsumer class which implements the complementary part of the above

    TAKE CARE about security when using it: you generally do NOT want to allow anyone to be able to post commands to the
    queue and execute them blindly.
    A basic limitation you can implement is to whitelist the commands available for execution via queue messages; this
    is set up via parameters.yml

* MessageConsumer and MessageProducer classes to distribute execution of HTTP calls
    Useful f.e. to distribute link-checking tasks to many concurrent workers 

* MessageConsumer and MessageProducer classes to distribute execution of XMLRPC calls to remote servers
    (note that you will need to install the phpxmlrpc\phpxmlrpc package for this to work)

* MessageConsumer and MessageProducer classes which can distribute execution of methods exposed by Symfony services.
    A basic limitation you can implement is to whitelist the service methods  available for execution via queue messages;
    this is set up via parameters.yml

* An event: kaliop_queueing.message_received, which your services can listen to by usage of tag kaliop_queueing.event_listener
    This allows to filter received messages to introduce e.g. security, logging or other cross-cutting concerns.
    To 'swallow' a consumed message, your event listener should simply call stopPropagation() on the event 

* A console command used to consume messages, similar to the rabbitmq:consumer command but with more options, such as
  support for multiple driver and timeouts

* A console command used to 'daemonize' (a.k.a. restart if not executing) multiple php processes which are 'workers'
    (a.k.a. message consumers)

* A console command used to troubleshoot queues, by dumping their config and current message count as well as purging
  and deleting them (exact capabilities depend on each driver)

* A console command used to troubleshoot drivers - at the moment it can simply list them 

* A MessageProducer class from which message producers can be derived

* A MessageConsumer class from which message consumers can be derived


## Getting started

### Setup

1. Install and start RabbitMQ.
    Yo do not need to set up exchanges and queues at this time, but installing the Management Plugin is a good idea

2. Install the bundle.
    Make sure you have the oldsound/rabbitmq-bundle package installed in Symfony
    (this happens automatically if you are using Composer)

3. Enable *both* the KaliopQueueingBundle bundle and the OldSoundRabbitMqBundle, in your kernel class registerBundles().    

4. Clear all caches if not on a dev environment

### Configure - testing

We will now configure the server so that console commands execution can be delegated to remote systems.
For a start, the same Symfony installation will be used both as message producer and consumer.

5. Test first that a simple console command from this bundle can be executed locally 

        php console kaliop_queueing:echoback "hello world" -f "testoututput.txt" 

6. Check that the 'rabbitmq' driver for the bundle is registered:

        php console kaliop_queueing:managedriver list

7. In a config file, define producers and consumers according to rabbitmq-bundle docs

    the rabbitmq_sample.yml file in Resources/config has an example of configuration to define a queue used to
    distribute execution of symfony console commands

8. Check that the producers and consumers are properly set up by listing them:

        php console kaliop_queueing:managequeue list

    In the results, queues tagged 1 are producers, queues tagged 2 are consumers

9. Start a consumer, putting it in the background

        php console kaliop_queueing:consumer <queue> --label="testconsumer" -w &

    Note that <queue> above is to be substituted with the name of a consumer from step 8 

10. Test what happens now: when you queue execution of echoback, the consumer should trigger it immediately

        php console kaliop_queueing:queuecommand <queue> kaliop_queueing:echoback "hello world again" option.f.testoututput2.txt
        cat testoututput2.txt
        tail logs/<env>.log

    Note that <queue> above is to be substituted with the name of a producer from step 8

11. Kill the consumer, remove the created testoutput files

### Configure - moving to production

12. Implement custom message producers and consumers, hook them to Rabbit queues via configuration

13. Schedule execution of the watchdog so that it will start consumers automatically:

    - In a config file, define as parameters those workers which you want to run as daemons.
      See the parameters.yml file for more details

    - set up in crontab something akin to:

            * * * * * cd $APP && $PHP console kaliop_queueing:workerswatchdog > /dev/null

14. PROPERLY SECURE YOUR NETWORK !!!

    If you are running the consumers which execute Symfony console commands or Symfony services, be warned that for the
    moment they provide no authentication mechanism at all .
    Anyone who can send messages to their queue can have them execute the relevant code. 

15. If you are running the consumers which execute Symfony console commands or Symfony services, set up at least some
    basic security via filtering of the accepted messages by configuring values in parameters.yml


## Code samples

### Sending a message

1. Setting up a new message producer

    - create a subclass of MessageProducer;
    - implement a `publish` method which calls `doPublish` internally;
    - declare it as service

2. Execution

        $driver = $container->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        $container->get('a_message_producer_service')
            ->setDriver($driver)
            ->setQueueName($queueName);
            ->publish($stuff...);

3. If you want to make the above code simpler, you can define specific message producers as services - as long as you are
    on Symfony 2.4 or later.

    Example configuration: this service uses the 'sqs' driver and a queue named 'aQueue'

        services:
            hello.world.producer:
                class: %kaliop_queueing.message_producer.console_command.class%
                calls:
                    - [ setDriver, [ "@=service('kaliop_queueing.drivermanager').getDriver('sqs')" ] ]
                    - [ setQueueName, [ 'aQueue' ] ]

    And code:

        $container->get('hello.world.producer')->publish($cmd, $args);

### Receiving a message

1. Setting up a new message consumer

    - create a subclass of MessageConsumer;
    - implement a `consume` method;
    - declare it as service
    - hook up the service to the desired queue using driver-specific configuration

2. Execution

        $driver = $container->get('kaliop_queueing.drivermanager')->getDriver($driverName);
        $driver->getConsumer($queueName)
            // optional
            ->setRoutingKey($key);
            ->consume($nrOfMessages);


## Console commands available:

* php console kaliop_queueing:queuecommand [-i=<driver>] [-ttl=<secs>] [-r=<routing key>] [--novalidate] <producer> <command> <args*>

    To send to a queue a message specifying execution of the given symfony console command

* php console kaliop_queueing:queuemessage [-i=<driver>] [-ttl=<secs>] [-r=<routing key>] [-c=<content-type>] [-m=<repeat>] <producer> <body>

    To send to a queue a message in a pre-formatted payload

* php console kaliop_queueing:consumer [-w] [-r=<routing key>] [-m=<messages-to-consume>] <consumer>

    To start a worker process which consumes messages from the specified queue.

* php console kaliop_queueing:managedriver list [<driver>]

    To manage a given driver, or list installed drivers
    
* php console kaliop_queueing:managequeue [-i=<driver>] list|purge|delete|info [<producer>]

    To manage a given queue: get info about its state, or purge it from messages. Also to list all queues

* php console kaliop_queueing:watchdog start|stop|check

    To check that all the configured worker processes are executing and restart them if they are not


## Events available:

* Kaliop\QueueingBundle\Event\EventsList::MESSAGE_RECEIVED emitted when a message is gotten from the queue, before it is consumed.
    It can be used to cancel the consuming.

* Kaliop\QueueingBundle\Event\EventsList::MESSAGE_CONSUMED emitted when a message from the queue has been consumed.

* Kaliop\QueueingBundle\Event\EventsList::PROCESS_STARTED emitted when the watchdog starts a process

* Kaliop\QueueingBundle\Event\EventsList::PROCESS_STOPPED emitted when the watchdog stops a process

Note : these events are not dispatched by Symfony2's event dispatcher as such you cannot register listeners with the
``kernel.event_listener`` tag, or the ``@DI\Observe`` annotation. See the examples in services.yml on how to use them.


## More docs

* a slide set, prepared for phpsummercamp 2015: https://docs.google.com/presentation/d/16rjSyejWGx4z7lIUYzvB5sXS8wMuHQc5N3QdIbkgj1A/pub?start=false&loop=false&delayms=10000#slide=id.p


## Similar packages

The work done here is by no means unique; it seems that there are already a lot of php packages dealing with queues
and abstracting away from the details of the transport protocols. 

What follows is neither an endorsement statement, nor a definitive list by any measure, more of a reminder for the
developers of this library of where to turn to to get inspiration and borrow code from ;-)

* zendframework/zend-queue - https://github.com/zendframework/ZendQueue

* slm/queue - https://github.com/juriansluiman/SlmQueue

* jms/job-queue-bundle - https://github.com/schmittjoh/JMSJobQueueBundle

* bernard/bernard - https://github.com/bernardphp/bernard

* wowo/wowo-queue-bundle - https://github.com/wowo/WowoQueueBundle

* grimkirill/queue - https://github.com/grimkirill/queue

* swarrot/swarrot - https://github.com/swarrot/swarrot


[![License](https://poser.pugx.org/kaliop/queueingbundle/license)](https://packagist.org/packages/kaliop/queueingbundle)
[![Latest Stable Version](https://poser.pugx.org/kaliop/queueingbundle/v/stable)](https://packagist.org/packages/kaliop/queueingbundle)
[![Total Downloads](https://poser.pugx.org/kaliop/queueingbundle/downloads)](https://packagist.org/packages/kaliop/queueingbundle)

[![Build Status](https://travis-ci.org/kaliop-uk/kueueingbundle.svg?branch=master)](https://travis-ci.org/kaliop-uk/kueueingbundle)
