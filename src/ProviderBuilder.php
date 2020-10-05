<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerServiceEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;
use Crell\Tukio\OrderedCollection\OrderedCollection;

class ProviderBuilder implements OrderedProviderInterface, \IteratorAggregate
{
    use ProviderUtilitiesTrait;

    /** @var OrderedCollection */
    protected $listeners;

    public function __construct()
    {
        $this->listeners = new OrderedCollection();
    }

    public function addListener(callable $listener, int $priority = 0, string $id = null, string $type = null): string
    {
        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItem($entry, $priority, $id);
    }

    public function addListenerBefore(string $before, callable $listener, string $id = null, string $type = null): string
    {
        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemBefore($before, $entry, $id);
    }

    public function addListenerAfter(string $after, callable $listener, string $id = null, string $type = null): string
    {
        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemAfter($after, $entry, $id);
    }

    public function addListenerService(string $service, string $method, string $type, int $priority = 0, string $id = null): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);

        return $this->listeners->addItem($entry, $priority, $id);
    }

    public function addListenerServiceBefore(string $before, string $service, string $method, string $type, string $id = null): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);

        return $this->listeners->addItemBefore($before, $entry, $id);
    }

    public function addListenerServiceAfter(string $after, string $service, string $method, string $type, string $id = null): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);

        return $this->listeners->addItemAfter($after, $entry, $id);
    }

    public function addSubscriber(string $class, string $service): void
    {
        // @todo This method is identical to the one in RegisterableListenerProvider. Is it worth merging them?

        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class))) {
            /** @var SubscriberInterface */
            $class::registerListeners($proxy);
        }

        try {
            $rClass = new \ReflectionClass($class);
            $methods = $rClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                $methodName = $rMethod->getName();
                if (!in_array($methodName, $proxy->getRegisteredMethods()) && strpos($methodName, 'on') === 0) {
                    $params = $rMethod->getParameters();
                    $type = $params[0]->getType()->getName();
                    $this->addListenerService($service, $rMethod->getName(), $type);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }

    public function getIterator()
    {
        yield from $this->listeners;
    }

    protected function getListenerEntry(callable $listener, string $type) : ListenerEntry
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
