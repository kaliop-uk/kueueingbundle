# Kaliop Queueing Bundle

A Symfony Bundle offering functionality related to message queuing systems.

Ideally, it should shield the rest of the app from the messaging system in use
(as of now is the only one supported is RabbitMQ via the RabbitMqBundle;
other brokers supporting the amqp protocol are likely to work but untested)

The main use cases supported so far are:

- making it easy to write message producers and consumers
- making it easy to introduce a queueing system in an existing application, allowing remote execution of commands/services/etc
- helping with the creation of job-processing message consumers which work as daemons, in php, overcoming the inherent
  stability problems.


## Features implemented

* A MessageProducer class which can be used to distribute execution of Symfony console commands to distributed workers

* A console command which can be used to test the above (distribute execution of any console command)

* A MessageConsumer class which implements the complementary part of the above

    TAKE CARE about security when using it: you generally do NOT want to allow anyone to be able to post commands to the
    queue and execute them blindly.
    A basic limitation you can implement is to whitelist the commands available for execution via queue messages; this
    is set up via parameters.yml

* MessageConsumer and MessageProducer classes to distribute execution of XMLRPC calls to remote servers
    (note that you will need to install the phpxmlrpc\phpxmlrpc package for this to work)

* MessageConsumer and MessageProducer classes which can distribute execution of methods exposed by Symfony services.
    A basic limitation you can implement is to whitelist the service methods  available for execution via queue messages;
    this is set up via parameters.yml

* An event: kaliop_queueing.message_received, which your services can listen to by usage of tag kaliop_queueing.event_listener
    This allows to filter received messages to introduce e.g. security, logging or other cross-cutting concerns.
    To 'swallow' a consumed message, your event listener should simply call stopPropagation() on the event 

* A console command used to 'daemonize' (a.k.a. restart if not executing) multiple php processes which are 'workers'
    (a.k.a. message consumers)

* A console command used to troubleshoot queues, by dumping their config and current message count as well as purging
  and deleting them

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

6. In a config file, define workers and producers according to rabbitmq-bundle docs

    the rabbitmq_sample.yml file in Resources/config has an example of configuration to define a queue used to
    distribute execution of symfony console commands

7. Start a consumer, putting it in the background

        php console kaliop_queueing:consumer <queue> --label="testconsumer" -w &

    Note that <queue> above is to be substituted with the name of a consumer from the old_sound_rabbit_mq configuration 

8. Test what happens now: when you queue execution of echoback, the consumer should trigger it immediately

        php console kaliop_queueing:queuecommand <queue> kaliop_queueing:echoback "hello world again" option.f.testoututput2.txt
        cat testoututput2.txt
        tail logs/<env>.log

9. Kill the consumer, remove the created testoutput files

### Configure - moving to production

10. Implement custom message producers and consumers, hook them to Rabbit queues via configuration

11. Schedule execution of the watchdog so that it will start consumers automatically:

    - In a config file, define as parameters those workers which you want to run as daemons.
      See the parameters.yml file for more details

    - set up in crontab something akin to:

            * * * * * cd $APP && $PHP console kaliop_queueing:workerswatchdog > /dev/null

12. PROPERLY SECURE YOUR NETWORK !!!

    If you are running the consumers which execute Symfony console commands or Symfony services, be warned that for the
    moment they provide no authentication mechanism at all .
    Anyone who can send messages to their queue can have them execute the relevant code. 

13. If you are running the consumers which execute Symfony console commands or Symfony services, set up at least some
    basic security via filtering of the accepted messages by configuring values in parameters.yml


## Console commands available:

* php console kaliop_queueing:queuecommand [-ttl=<secs>] [--novalidate] <producer> <command> <args*>

    To send to a queue a message specifying execution of the given symfony console command

* php console kaliop_queueing:queuemessage [-ttl=<secs>] [-k=<routing key>] [-c=<content-type>] [-r=<repeat>] <producer> <body>

    To send to a queue a message in a pre-formatted payload

* php console kaliop_queueing:consumer [-w] <consumer>

    To start a worker process which consumes messages from the specified queue.
    NB: this is preferred over rabbitmq: consumer, as it adds a couple of features

* php console kaliop_queueing:managequeue purge|delete|info <producer>

    To manage a given queue: get info about its state, or purge it from messages

* php console kaliop_queueing:watchdog

    To check that all the configured worker processes are executing and restart them if they are not


## Events available:

* Kaliop\QueueingBundle\Events\EventsList::MESSAGE_RECEIVED emitted when a message is gotten from the queue, before it is consumed

* Kaliop\QueueingBundle\Events\EventsList::PROCESS_STARTED emitted when the watchdog starts a process

* Kaliop\QueueingBundle\Events\EventsList::PROCESS_STOPPED emitted when the watchdog stops a process

Note : these events are not dispatched by Symfony2's event dispatcher as such you cannot register listeners with the
``kernel.event_listener`` tag, or the ``@DI\Observe`` annotation. See the examples in services.yml on how to use them.


## Todo

* more docs: graphical schemas representing the bits and pieces and data flow

* add an interface to be implemented by message producers (can we do it with a method with variable arguments?)

* add an optional timeout parameter for remote command execution (not the time to wait in the queue)

* make MessageProducers self-documenting (eg. via usage of xsd or jsonschema)

* allow consumers to easily validate received data: see f.e. https://github.com/justinrainbow/json-schema

* the usage of the term "queue" should probably be better explained (it is not the same as rabbit queue name)

* set up filters for existing producers:
    - set up a list of target servers with options for xmlrpc
