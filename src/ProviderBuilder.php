<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerServiceEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;
use Crell\OrderedCollection\OrderedCollection;

class ProviderBuilder implements OrderedProviderInterface, \IteratorAggregate
{
    use ProviderUtilities;

    /**
     * @var OrderedCollection<callable>
     */
    protected OrderedCollection $listeners;

    /**
     * @var array<class-string>
     */
    protected array $optimizedEvents = [];

    public function __construct()
    {
        $this->listeners = new OrderedCollection();
    }

    /**
     * Pre-specify an event class that should have an optimized listener list built.
     *
     * @param class-string ...$events
     */
    public function optimizeEvents(string ...$events): void
    {
        $this->optimizedEvents = [...$this->optimizedEvents, ...$events];
    }

    /**
     * @return array<class-string>
     */
    public function getOptimizedEvents(): array
    {
        return $this->optimizedEvents;
    }

    public function listener(callable $listener, ?Order $order = null, ?string $id = null, ?string $type = null): string
    {
        $attrib ??= $this->getAttributes($listener)[0] ?? null;
        $id ??= $order->id ?? $attrib?->id ?? $this->getListenerId($listener);
        $type ??= $order->type ?? $attrib?->type ?? $this->getType($listener);
        $order ??= $attrib?->order;

        $entry = $this->getListenerEntry($listener, $type);

        return match (true) {
            $order instanceof OrderBefore => $this->listeners->addItemBefore($order->before, $entry, $id),
            $order instanceof OrderAfter => $this->listeners->addItemAfter($order->after, $entry, $id),
            $order instanceof OrderPriority => $this->listeners->addItem($entry, $order->priority, $id),
            default => $this->listeners->addItem($entry, id: $id),
        };
    }

    public function addListener(callable $listener, ?int $priority = null, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, $priority ? Order::Priority($priority) : null, $id, $type);
    }

    public function addListenerBefore(string $before, callable $listener, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, $before ? Order::Before($before) : null, $id, $type);
    }

    public function addListenerAfter(string $after, callable $listener, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, $after ? Order::After($after) : null, $id, $type);
    }

    public function addListenerService(string $service, string $method, string $type, ?int $priority = null, ?string $id = null): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);
        $priority ??= 0;

        return $this->listeners->addItem($entry, $priority, $id);
    }

    public function addListenerServiceBefore(string $before, string $service, string $method, string $type, ?string $id = null): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);

        return $this->listeners->addItemBefore($before, $entry, $id);
    }

    public function addListenerServiceAfter(string $after, string $service, string $method, string $type, ?string $id = null): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);

        return $this->listeners->addItemAfter($after, $entry, $id);
    }

    public function addSubscriber(string $class, string $service): void
    {
        // @todo This method is identical to the one in OrderedListenerProvider. Is it worth merging them?

        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class), true)) {
            /** @var SubscriberInterface $class */
            $class::registerListeners($proxy);
        }

        try {
            $rClass = new \ReflectionClass($class);
            $methods = $rClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                $methodName = $rMethod->getName();
                if (str_starts_with($methodName, 'on') && !in_array($methodName, $proxy->getRegisteredMethods(), true)) {
                    $params = $rMethod->getParameters();
                    // getName() is not part of the declared reflection API, but it's there.
                    // @phpstan-ignore-next-line
                    $type = $params[0]->getType()->getName();
                    $this->addListenerService($service, $rMethod->getName(), $type);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }

    public function getIterator(): \Traversable
    {
        yield from $this->listeners;
    }

    protected function getListenerEntry(callable $listener, string $type): ListenerEntry
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
