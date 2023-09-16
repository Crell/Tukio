<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Crell\OrderedCollection\OrderedCollection;
use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class OrderedListenerProvider implements ListenerProviderInterface, OrderedProviderInterface
{
    use ProviderUtilities;

    /**
     * @var OrderedCollection<callable>
     */
    protected OrderedCollection $listeners;

    public function __construct(protected ?ContainerInterface $container = null)
    {
        $this->listeners = new OrderedCollection();
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        /** @var ListenerEntry $listener */
        foreach ($this->listeners as $listener) {
            if ($event instanceof $listener->type) {
                yield $listener->listener;
            }
        }
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

    protected function getListenerEntry(callable $listener, string $type): ListenerEntry
    {
        // String means it's a function name, and that's safe.
        if (is_string($listener)) {
            return new ListenerFunctionEntry($listener, $type);
        }
        // This is how we recognize a static method call.
        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return new ListenerStaticMethodEntry($listener[0], $listener[1], $type);
        }

        return new ListenerEntry($listener, $type);
    }

    public function addListener(callable $listener, ?int $priority = null, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, ($priority !== null) ? Order::Priority($priority) : null, $id, $type);
    }

    public function addListenerBefore(string $before, callable $listener, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, $before ? Order::Before($before) : null, $id, $type);
    }

    public function addListenerAfter(string $after, callable $listener, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, $after ? Order::After($after) : null, $id, $type);
    }

    public function listenerService(string $service, string $method, string $type, ?Order $order = null, ?string $id = null): string
    {
        $id ??= $service . '-' . $method;
        return $this->listener($this->makeListenerForService($service, $method), $order, $id, $type);
    }

    public function addListenerService(string $service, string $method, string $type, ?int $priority = null, ?string $id = null): string
    {
        return $this->listenerService($service, $method, $type, ($priority !== null) ? Order::Priority($priority) : null, $id);
    }

    public function addListenerServiceBefore(string $before, string $service, string $method, string $type, ?string $id = null): string
    {
        return $this->listenerService($service, $method, $type, $before ? Order::Before($before) : null, $id);
    }

    public function addListenerServiceAfter(string $after, string $service, string $method, string $type, ?string $id = null): string
    {
        return $this->listenerService($service, $method, $type, $after ? Order::After($after) : null, $id);
    }

    public function addSubscriber(string $class, string $service): void
    {
        $proxy = $this->addSubscribersByProxy($class, $service);

        try {
            $methods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);

            $methods = array_filter($methods, fn(\ReflectionMethod $r)
                => !in_array($r->getName(), $proxy->getRegisteredMethods(), true));

            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
               $this->addSubscriberMethod($rMethod, $class, $service);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }

    protected function addSubscribersByProxy(string $class, string $service): ListenerProxy
    {
        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class), true)) {
            /** @var SubscriberInterface $class */
            $class::registerListeners($proxy);
        }
        return $proxy;
    }

    /**
     * @return array<ListenerAttribute>
     */
    protected function findAttributesOnMethod(\ReflectionMethod $rMethod): array
    {
        $attributes = array_map(static fn (\ReflectionAttribute $attrib): object
        => $attrib->newInstance(), $rMethod->getAttributes(Listener::class, \ReflectionAttribute::IS_INSTANCEOF));

        return $attributes;
    }

    protected function addSubscriberMethod(\ReflectionMethod $rMethod, string $class, string $service): void
    {
        $methodName = $rMethod->getName();
        $params = $rMethod->getParameters();

        if (count($params) < 1) {
            // Skip this method, as it doesn't take arguments.
            return;
        }

        $attributes = $this->findAttributesOnMethod($rMethod);
        /** @var Listener $attrib */
        $attrib = $attributes[0] ?? null;

        if (str_starts_with($methodName, 'on') || $attrib) {
            $paramType = $params[0]->getType();

            $id = $attrib->id ?? $service . '-' . $methodName;
            $type = $attrib->type ?? $paramType?->getName() ?? throw InvalidTypeException::fromClassCallable($class, $methodName);

            $this->listener($this->makeListenerForService($service, $methodName), $attrib?->order, $id, $type);
        }
    }

    /**
     * Creates a callable that will proxy to the provided service and method.
     *
     * @param string $serviceName
     *   The name of a service.
     * @param string $methodName
     *   A method on the service.
     * @return callable
     *   A callable that proxies to the the provided method and service.
     */
    protected function makeListenerForService(string $serviceName, string $methodName): callable
    {
        if (!$this->container) {
            throw new ContainerMissingException();
        }

        // We cannot verify the service name as existing at this time, as the container may be populated in any
        // order.  Thus the referenced service may not be registered now but could be registered by the time the
        // listener is called.

        // Fun fact: We cannot auto-detect the listener target type from a container without instantiating it, which
        // defeats the purpose of a service registration. Therefore this method requires an explicit event type. Also,
        // the wrapping listener must listen to just object.  The explicit $type means it will still get only
        // the right event type, and the real listener can still type itself properly.
        $container = $this->container;
        return static fn (object $event) => $container->get($serviceName)->$methodName($event);
    }
}
