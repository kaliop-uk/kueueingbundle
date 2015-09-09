
# Ver 0.2

* NEW: introduced a new Event: MessageConsumed (triggered after message processing)

* NEW: the Publisher classes now implement a BatchPublish method for optimized sending of multiple messages

* NEW: introduced fluent interfaces (for all setter methods)

* NEW: all MessageConsumer classes now return a value from their consume() method  

* NEW: introduced protection against recursion for MessageConsumer::decodeAndConsume

* NEW: added an interface for MessageProducer classes

* CHANGED: changed the MessageReceived event to simplify it a bit

* CHANGED: cli commands use '-i' to specify the driver to use instead of '-b'


# Ver 0.1

* first release announced to the world