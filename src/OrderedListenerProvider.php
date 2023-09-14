<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Crell\OrderedCollection\OrderedCollection;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class OrderedListenerProvider implements ListenerProviderInterface, OrderedProviderInterface
{
    use ProviderUtilities;

    /**
     * @var OrderedCollection<callable>
     */
    protected OrderedCollection $listeners;

    protected ?ContainerInterface $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->listeners = new OrderedCollection();
        $this->container = $container;
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

    public function addListener(callable $listener, ?int $priority = null, ?string $id = null, ?string $type = null): string
    {
        $attributes = $this->getAttributes($listener);
        $def = $attributes[0] ?? new Listener();

        if ($priority) {
            $def->priority = $priority;
        }
        if ($id) {
            $def->id = $id;
        }
        if ($type) {
            $def->type = $type;
        }

        $def->id ??= $this->getListenerId($listener);
        $def->type ??= $this->getType($listener);

        $generatedId = match (true) {
            $def->before !== null => $this->listeners->addItemBefore($def->before, new ListenerEntry($listener, $def->type), $def->id),
            $def->after !== null => $this->listeners->addItemAfter($def->after, new ListenerEntry($listener, $def->type), $def->id),
            $def->priority !== null => $this->listeners->addItem(new ListenerEntry($listener, $def->type), $def->priority, $def->id),
            default => $this->listeners->addItem(new ListenerEntry($listener, $def->type), $priority ?? 0, $def->id),
        };

        return $generatedId;
    }

    public function addListenerBefore(string $before, callable $listener, ?string $id = null, ?string $type = null): string
    {
        if ($attributes = $this->getAttributes($listener)) {
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                // The before-ness of this method call always overrides the attribute.
                $generatedId = $this->listeners->addItemBefore($before, new ListenerEntry($listener, $type), $id);
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemBefore($before, new ListenerEntry($listener, $type), $id);
    }

    public function addListenerAfter(string $after, callable $listener, ?string $id = null, ?string $type = null): string
    {
        if ($attributes = $this->getAttributes($listener)) {
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                // The after-ness of this method call always overrides the attribute.
                $generatedId = $this->listeners->addItemAfter($after, new ListenerEntry($listener, $type), $id);
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemAfter($after, new ListenerEntry($listener, $type), $id);
    }

    public function addListenerService(string $service, string $method, string $type, ?int $priority = null, ?string $id = null): string
    {
        $id = $id ?? $service . '-' . $method;
        $priority = $priority ?? 0;
        return $this->addListener($this->makeListenerForService($service, $method), $priority, $id, $type);
    }

    public function addListenerServiceBefore(string $before, string $service, string $method, string $type, ?string $id = null): string
    {
        $id = $id ?? $service . '-' . $method;
        return $this->addListenerBefore($before, $this->makeListenerForService($service, $method), $id, $type);
    }

    public function addListenerServiceAfter(string $after, string $service, string $method, string $type, ?string $id = null): string
    {
        $id = $id ?? $service . '-' . $method;
        return $this->addListenerAfter($after, $this->makeListenerForService($service, $method), $id, $type);
    }

    public function addSubscriber(string $class, string $service): void
    {
        $proxy = $this->addSubscribersByProxy($class, $service);

        try {
            $methods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);

            // Explicitly registered methods ignore all auto-registration mechanisms.
            $methods = array_filter($methods, static function(\ReflectionMethod $refm) use ($proxy) {
                return !in_array($refm->getName(), $proxy->getRegisteredMethods());
            });

            // Once we require PHP 7.4, replace the above with this line.
            //$methods = array_filter($methods, fn(\ReflectionMethod $r) => !in_array($r->getName(), $proxy->getRegisteredMethods()));

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
        if (in_array(SubscriberInterface::class, class_implements($class))) {
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
        => $attrib->newInstance(), $rMethod->getAttributes(ListenerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF));

        return $attributes;
    }

    protected function addSubscriberMethod(\ReflectionMethod $rMethod, string $class, string $service): void
    {
        $methodName = $rMethod->getName();

        $attributes = $this->findAttributesOnMethod($rMethod);

        if (count($attributes)) {
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $params = $rMethod->getParameters();
                $paramType = $params[0]->getType();
                // getName() is not part of the declared reflection API, but it's there.
                // @phpstan-ignore-next-line
                $type = $attrib->type ?? $paramType?->getName();
                if (is_null($type)) {
                    throw InvalidTypeException::fromClassCallable($class, $methodName);
                }
                if ($attrib instanceof ListenerBefore) {
                    $this->addListenerServiceBefore($attrib->before, $service, $methodName, $type, $attrib->id);
                } elseif ($attrib instanceof ListenerAfter) {
                    $this->addListenerServiceAfter($attrib->after, $service, $methodName, $type, $attrib->id);
                } elseif ($attrib instanceof ListenerPriority) {
                    $this->addListenerService($service, $methodName, $type, $attrib->priority, $attrib->id);
                } else {
                    $this->addListenerService($service, $methodName, $type, null, $attrib->id);
                }
            }
        } elseif (str_starts_with($methodName, 'on')) {
            $params = $rMethod->getParameters();
            $type = $params[0]->getType();
            if (is_null($type)) {
                throw InvalidTypeException::fromClassCallable($class, $methodName);
            }
            // getName() is not part of the declared reflection API, but it's there.
            // @phpstan-ignore-next-line
            $this->addListenerService($service, $rMethod->getName(), $type->getName());
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
        return static function (object $event) use ($serviceName, $methodName, $container): void {
            $container->get($serviceName)->$methodName($event);
        };
    }
}
