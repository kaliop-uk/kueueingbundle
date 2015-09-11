
# Ver 0.2

* NEW: introduced a new Event: MessageConsumed (triggered after message processing)
       The Monitor event listener can be tagged to listen to this event and log debug information

* NEW: the Publisher classes now implement a BatchPublish method for optimized sending of multiple messages

* NEW: the Consume method of Consumer classes now accepts a $timeout optional parameter.
       This is also true of the kaliop_queueing:consumer console command 

* NEW: introduced fluent interfaces (for all setter methods)

* NEW: all MessageConsumer classes now return a value from their consume() method  

* NEW: added an interface for MessageProducer classes

* NEW: the QueueManager classes (and console command) now take optional parameters for all actions

* NEW: added a new service which can be used as MessageConsumed listener to help testing: kaliop_queueing.message_consumer.filter.accumulator 

* NEW: introduced protection against recursion for MessageConsumer::decodeAndConsume

* CHANGED: changed the MessageReceived event to simplify it a bit

* CHANGED: cli commands use '-i' to specify the driver to use instead of '-b'


# Ver 0.1

* first release announced to the world
