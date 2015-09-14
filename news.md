
# Ver 0.2

* NEW: introduced a new Event: MessageConsumed (triggered after message processing)
       The Monitor event listener can be tagged to listen to this event and log debug information

* NEW: the Publisher classes now implement a BatchPublish method for optimized sending of multiple messages

* NEW: the Consume method of Consumer classes now accepts a $timeout optional parameter.
       This is also true of the kaliop_queueing:consumer console command 

* NEW: introduced fluent interfaces for all setter methods

* NEW: all MessageConsumer classes now return a value from their consume() method  

* NEW: added an interface for MessageProducer classes

* NEW: the QueueManager classes (and console command) now take optional parameters for all actions.
       The exact parameters depend on the driver+action combination 

* NEW: added a new service which can be used as MessageConsumed listener to help testing: kaliop_queueing.message_consumer.filter.accumulator 

* NEW: introduced protection against recursion for MessageConsumer::decodeAndConsume

* CHANGED: the ConsumerInterface now sports a method setCallback() 

* CHANGED: changed the MessageReceived event to simplify it a bit

* CHANGED: cli commands use '-i' to specify the driver to use instead of '-b'

* CHANGED: cli command kaliop_queueing:managequeue uses '-o option=value' to specify options for the remote command

* CHANGED: cli command `kaliop_queueing:managequeue list` has been renamed `kaliop_queueing:managequeue list-configured`
           to avoid confusion between configured bundle queues and queues/exchanges existing on the broker.
           It now works in prod environments and not only in dev

* FIXED: RabbitMQ Consumers can not change the routing key associated with their queue. The bundle now throws an exception  
         if this is attempted


# Ver 0.1

* first release announced to the world
