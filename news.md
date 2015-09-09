
# Ver 0.2

* introduced a new Event: MessageConsumed (triggered after message processing)

* the Publisher classes now have to implement a BatchPublish method for optimized sending of multiple messages

* introduced fluent interfaces (for all setter methods)

* added an interface for MessageProducer classes

* changed the MessageReceived event to simplify it a bit

* introduced protection against recursion for MessageConsumer::decodeAndConsume


# Ver 0.1

* first release announced to the world