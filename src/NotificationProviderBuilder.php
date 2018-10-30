<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerServiceEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;
use Crell\Tukio\OrderedCollection\OrderedCollection;

class NotificationProviderBuilder implements ProviderBuilderInterface, RegisterableNotificationListenerProviderInterface
{
    use ProviderUtilitiesTrait;

    /** @var array */
    protected $listenerEntries = [];

    public function addListener(callable $listener, string $type = null): void
    {
        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));

        $this->listenerEntries[] = $entry;
    }

    public function addListenerService(string $serviceName, string $methodName, string $type): void
    {
        $this->listenerEntries[] = new ListenerServiceEntry($serviceName, $methodName, $type);
    }

    public function addSubscriber(string $class, string $serviceName): void
    {
        // @todo This method is identical to the one in RegisterableListenerProvider. Is it worth merging them?

        $proxy = new MessageListenerProxy($this, $serviceName, $class);

        // Explicit registration is opt-in.
        if (in_array(MessageSubscriberInterface::class, class_implements($class))) {
            /** @var TaskSubscriberInterface */
            $class::registerListeners($proxy);
        }

        try {
            $rClass = new \ReflectionClass($class);
            $methods = $rClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                $methodName = $rMethod->getName();
                if (!in_array($methodName, $proxy->getRegisteredMethods()) && strpos($methodName, 'on') !== false) {
                    $params = $rMethod->getParameters();
                    $type = $params[0]->getType()->getName();
                    $this->addListenerService($serviceName, $rMethod->getName(), $type);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }

    public function getIterator()
    {
        yield from $this->listenerEntries;
    }

    protected function getListenerEntry(callable $listener, string $type)
    {
        // We can't serialize a closure.
        if ($listener instanceof \Closure) {
            throw new \InvalidArgumentException('Closures cannot be used in a compiled listener provider.');
        }
        // String means it's a function name, and that's safe.
        if (is_string($listener)) {
            return new ListenerFunctionEntry($listener, $type);
        }
        // This is how we recognize a static method call.
        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return new ListenerStaticMethodEntry($listener[0], $listener[1], $type);
        }
        // Anything else isn't safe to serialize, so reject it.
        throw new \InvalidArgumentException('That callable type cannot be used in a compiled listener provider.');
    }
}
