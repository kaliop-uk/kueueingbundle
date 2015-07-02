# Kaliop Queueing Bundle

A Symfony Bundle offering functionality related to message queuing systems.

Ideally, it should shield the rest of the app from the messaging system in use
(RabbitMQ via the RabbitMqBundle is the only one supported as of now)


## Features implemented

* A MessageProducer class from which message producers can be derived

* A MessageConsumer class from which message consumers can be derived

* A ConsoleCommand MessageProducer class which can be used to distribute execution of symfony console commands
  to distributed workers, and a corresponding console command.

* A ConsoleCommand MessageConsumer class which implements the complementary part of the above

    TAKE CARE about security when using it: you generally do NOT want to allow anyone to be able to post commands to the
    queue and execute them blindly

* A console command used to 'daemonize' (a.k.a. restart if not executing) php process which are 'workers' (a.k.a. message consumers)

* A console command use to troubleshoot queues, by dumping their config and current message count as well as purging
  and deleting them


## Getting started

1. Install and start rabbitmq

2. Make sure you have the oldsound/rabbitmq-bundle package installed in Symfony

3. Enable the bundle. Clear all caches if not on a dev environment

4. test that a simple console command can be executed locally 

       php console kaliop_queueing:echoback 'hello world' -f 'testoututput.txt'

5.  In a config file, define workers and producers according to rabbitmq-bundle docs

    the rabbitmq_sample.yml file has an example of configuration to define a queue used to distribute execution of
    symfony console commands

6. queue execution of the command kaliop_queueing:echoback

      php console kaliop_queueing:queuecommand <queue> kaliop_queueing:echoback 'hello world' option.f.testoututput.txt

7. start a consumer, putting it in the background

      php console kaliop_queueing:consumer <queue> --label='testconsumer' -w &

8. test what happens now: when you queue execution of echoback, the consumer should trigger it immediately

    php console kaliop_queueing:queuecommand <queue> kaliop_queueing:echoback 'hello world again' option.f.testoututput2.txt
    cat testoututput2.txt
    tail logs/<env>.log

9. kill the consumer, remove the created testoutput files

10. schedule execution of the watchdog so that it will start consumers automatically:

   - In a config file, define as parameters those workers which you want to run as daemons.
     See the parameters.yml file for more details

   - set up in crontab something akin to:

     * * * * * cd $APP && $PHP console kaliop_queueing:workerswatchdog > /dev/null

11. Implement custom message producers and consumers, hook them to Rabbit queues via configuration


12. PROPERLY SECURE YOUR NETWORK !!!

    If you are running the consumers which execute symfony console commands or symfony services, know that they provide
    no authentication mechanism at all for the moment.
    Anyone who can send messages to their queue can have them execute any code they want. 

## Console commands:

* php console kaliop_queueing:queuecommand [-ttl=<secs>] [--novalidate] <producer> <command> <args*>

    To send to a queue a message specifying execution of the given symfony console command

* php console rabbitmq:consumer -w <consumer>

    To start a worker process which consumes messages from the specified queue

* php console kaliop_queueing:watchdog

    To check that all the configured worker processes are executing and restart them if they are not


## Todo

* more docs: graphical schemas representing the bits and pieces and data flow

* add an interface to be implemented by message producers (can we do it with variable arguments?)

* add an optional timeout parameter for remote command execution (not the time to wait in the queue)

* make MessageProducers self-documenting (eg. via usage of xsd or jsonschema)

* allow consumers to easily validate received data: see f.e. https://github.com/justinrainbow/json-schema

* add a new message to remotely execute services (methods on services?) instead of console commands

* the usage of the term "queue" should probably be better explained (it is not the same as rabbit queue name)

* (test) windows support
