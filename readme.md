# Kaliop Queueing Bundle

A Symfony Bundle offering functionality related to message queuing systems.

Ideally, it should shield the rest of the app from the messaging system in use
(as of now is the only one supported is RabbitMQ via the RabbitMqBundle)


## Features implemented

* A MessageProducer class which can be used to distribute execution of Symfony console commands
  to distributed workers, and a corresponding console command.

* A MessageConsumer class which implements the complementary part of the above

    TAKE CARE about security when using it: you generally do NOT want to allow anyone to be able to post commands to the
    queue and execute them blindly.
    A basic limitation you can implement is to whitelist the commands available for execution via queue messages; this
    is set up via parameters.yml

* A CLI command which can be used to test the above (scheduling remote execution of console commands)

* MessageConsumer, MessageProducer and test cli command to schedule execution of XMLRPC calls to remote servers
   (note that you will need to install the phpxmlrpc\phpxmlrpc package for this to work)

* A MessageConsumer class which can execute methods exposed by Symfony services

* An event: kaliop_queueing.message_received, which your services can listen to by usage of tag kaliop_queueing.event_listener
    This allows to filter received messages to introduce e.g. security, logging or other cross-cutting concerns.
    To 'swallow' a consumed message, your event listener should simply call stopPropagation() on the event 
    
* A console command used to 'daemonize' (a.k.a. restart if not executing) php processes which are 'workers' (a.k.a.
    message consumers)

* A console command used to troubleshoot queues, by dumping their config and current message count as well as purging
  and deleting them

* A MessageProducer class from which message producers can be derived

* A MessageConsumer class from which message consumers can be derived


## Getting started

### Setup

1. Install and start RabbitMQ

2. Install the bundle.
    Make sure you have the oldsound/rabbitmq-bundle package installed in Symfony
    (this happens automatically if you are using Composer)

3. Enable the KaliopQueueingBundle bundle, as well as the OldSoundRabbitMqBundle, in your kernel class registerBundles.    

4. Clear all caches if not on a dev environment

### Configure - testing

We will now configure the server so that console commands can be executed on remote systems.
For a start, the same Symfony installation will be used both as message producer and consumer.

5. Test first that a simple console command can be executed locally 

       php console kaliop_queueing:echoback "hello world" -f "testoututput.txt" 
     
6. In a config file, define workers and producers according to rabbitmq-bundle docs

    the rabbitmq_sample.yml file in Resources/config has an example of configuration to define a queue used to
    distribute execution of symfony console commands

7. Queue execution of the command kaliop_queueing:echoback

      php console kaliop_queueing:queuecommand <queue> kaliop_queueing:echoback "hello world" option.f.testoututput.txt

    Note that <queue> above is to be substituted with the name of a producer from the old_sound_rabbit_mq configuration 
    
7. Start a consumer, putting it in the background

      php console kaliop_queueing:consumer <queue> --label="testconsumer" -w &

    Note that <queue> above is to be substituted with the name of a consumer from the old_sound_rabbit_mq configuration 

8. Test what happens now: when you queue execution of echoback, the consumer should trigger it immediately

    php console kaliop_queueing:queuecommand <queue> kaliop_queueing:echoback "hello world again" option.f.testoututput2.txt
    cat testoututput2.txt
    tail logs/<env>.log

9. Kill the consumer, remove the created testoutput files

### Configure - moving to production

10. Schedule execution of the watchdog so that it will start consumers automatically:

   - In a config file, define as parameters those workers which you want to run as daemons.
     See the parameters.yml file for more details

   - set up in crontab something akin to:

     * * * * * cd $APP && $PHP console kaliop_queueing:workerswatchdog > /dev/null

11. Implement custom message producers and consumers, hook them to Rabbit queues via configuration

12. PROPERLY SECURE YOUR NETWORK !!!

    If you are running the consumers which execute Symfony console commands or Symfony services, be warned that they
    provide no authentication mechanism at all for the moment.
    Anyone who can send messages to their queue can have them execute the relevant code. 

13. If you are running the consumers which execute Symfony console commands or Symfony services, set up at least some
    basic security via filtering of the accepted messages by configuring values in parameters.yml


## Console commands available:

* php console kaliop_queueing:queuecommand [-ttl=<secs>] [--novalidate] <producer> <command> <args*>

    To send to a queue a message specifying execution of the given symfony console command

* php console kaliop_queueing:queuexmlrpc [-ttl=<secs>] [--novalidate] <producer> <server> <method> <args*>

    To send to a queue a message specifying execution of an xmlrpc call

* php console rabbitmq:consumer -w <consumer>

    To start a worker process which consumes messages from the specified queue

* php console kaliop_queueing:managequeue purge|delete|info <producer>

    To manage a given queue: get info about its state, or purge it from messages

* php console kaliop_queueing:watchdog

    To check that all the configured worker processes are executing and restart them if they are not


## Todo

* more docs: graphical schemas representing the bits and pieces and data flow

* add an interface to be implemented by message producers (can we do it with variable arguments?)

* add an optional timeout parameter for remote command execution (not the time to wait in the queue)

* make MessageProducers self-documenting (eg. via usage of xsd or jsonschema)

* allow consumers to easily validate received data: see f.e. https://github.com/justinrainbow/json-schema

* add a new message producer to remotely execute services (methods on services?) instead of console commands

* the usage of the term "queue" should probably be better explained (it is not the same as rabbit queue name)

* set up filters for existing producers:
    - limit allowed services to a whitelist
    - limit allowed console commands to a whitelist
    - set up a list of target servers with options for xmlrpc
