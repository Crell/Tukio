<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\OrderedCollection\OrderedCollection;
use Crell\Tukio\Entry\ListenerEntry;
use Fig\EventDispatcher\ParameterDeriverTrait;

abstract class ProviderCollector implements OrderedProviderInterface
{
    use ParameterDeriverTrait;

    /**
     * @var OrderedCollection<callable>
     */
    protected OrderedCollection $listeners;

    public function __construct()
    {
        $this->listeners = new OrderedCollection();
    }

    public function listener(callable $listener, ?Order $order = null, ?string $id = null, ?string $type = null): string
    {
        $attrib = $this->getAttributes($listener)[0] ?? null;
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

            $methods = array_filter($methods, static fn(\ReflectionMethod $r)
            => !in_array($r->getName(), $proxy->getRegisteredMethods(), true));

            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                $this->addSubscriberMethod($rMethod, $class, $service);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
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
        /** @var ?Listener $attrib */
        $attrib = $attributes[0] ?? null;

        if (str_starts_with($methodName, 'on') || $attrib) {
            $paramType = $params[0]->getType();

            $id = $attrib->id ?? $service . '-' . $methodName;
            // getName() is not a documented part of the Reflection API, but it's always there.
            // @phpstan-ignore-next-line
            $type = $attrib->type ?? $paramType?->getName() ?? throw InvalidTypeException::fromClassCallable($class, $methodName);

            $this->listenerService($service, $methodName, $type, $attrib?->order, $id);
        }
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

    /**
     * @param class-string $class
     */
    protected function addSubscribersByProxy(string $class, string $service): ListenerProxy
    {
        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class) ?: [], true)) {
            /** @var SubscriberInterface $class */
            $class::registerListeners($proxy);
        }
        return $proxy;
    }

    /**
     * @return array<Listener>
     */
    protected function getAttributes(callable $listener): array
    {
        $ref = null;

        if ($this->isFunctionCallable($listener)) {
            /** @var string $listener */
            $ref = new \ReflectionFunction($listener);
        } elseif ($this->isClassCallable($listener)) {
            // PHPStan says you cannot use array destructuring on a callable, but you can
            // if you know that it's an array (which in context we do).
            // @phpstan-ignore-next-line
            [$class, $method] = $listener;
            $ref = (new \ReflectionClass($class))->getMethod($method);
        } elseif ($this->isObjectCallable($listener)) {
            // PHPStan says you cannot use array destructuring on a callable, but you can
            // if you know that it's an array (which in context we do).
            // @phpstan-ignore-next-line
            [$class, $method] = $listener;
            $ref = (new \ReflectionObject($class))->getMethod($method);
        }

        if (!$ref) {
            return [];
        }

        $attribs = $ref->getAttributes(Listener::class, \ReflectionAttribute::IS_INSTANCEOF);

        return array_map(fn(\ReflectionAttribute $attrib) => $attrib->newInstance(), $attribs);
    }

    /**
     * Tries to get the type of a callable listener.
     *
     * If unable, throws an exception with information about the listener whose type could not be fetched.
     *
     * @param callable $listener
     *   The callable from which to extract a type.
     *
     * @return string
     *   The type of the first argument.
     */
    protected function getType(callable $listener): string
    {
        try {
            $type = $this->getParameterType($listener);
        } catch (\InvalidArgumentException $exception) {
            if ($this->isClassCallable($listener) || $this->isObjectCallable($listener)) {
                /** @var array{0: class-string, 1: string} $listener */
                throw InvalidTypeException::fromClassCallable($listener[0], $listener[1], $exception);
            }
            if ($this->isFunctionCallable($listener) || $this->isClosureCallable($listener)) {
                throw InvalidTypeException::fromFunctionCallable($listener, $exception);
            }
            throw new InvalidTypeException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $type;
    }

    /**
     * Derives a predictable ID from the listener if possible.
     *
     * It's OK for this method to return null, as OrderedCollection will
     * generate a random ID if necessary.  It will also handle duplicates
     * for us.  This method is just a suggestion.
     *
     * @param callable $listener
     *   The listener for which to derive an ID.
     *
     * @return string|null
     *   The derived ID if possible or null if no reasonable ID could be derived.
     */
    protected function getListenerId(callable $listener): ?string
    {
        if ($this->isFunctionCallable($listener)) {
            // Function callables are strings, so use that directly.
            // @phpstan-ignore-next-line
            return (string)$listener;
        }
        if ($this->isClassCallable($listener)) {
            /** @var array{0: class-string, 1: string} $listener */
            return $listener[0] . '::' . $listener[1];
        }
        if (is_array($listener) && is_object($listener[0])) {
            return get_class($listener[0]) . '::' . $listener[1];
        }

        // Anything else we can't derive an ID for logically.
        return null;
    }

    /**
     * Determines if a callable represents a function.
     *
     * Or at least a reasonable approximation, since a function name may not be defined yet.
     *
     * @return bool
     *  True if the callable represents a function, false otherwise.
     */
    protected function isFunctionCallable(callable $callable): bool
    {
        // We can't check for function_exists() because it may be included later by the time it matters.
        return is_string($callable);
    }

    /**
     * Determines if a callable represents a method on an object.
     *
     * @return bool
     *  True if the callable represents a method object, false otherwise.
     */
    protected function isObjectCallable(callable $callable): bool
    {
        return is_array($callable) && is_object($callable[0]);
    }

    /**
     * Determines if a callable represents a closure/anonymous function.
     *
     * @return bool
     *  True if the callable represents a closure object, false otherwise.
     */
    protected function isClosureCallable(callable $callable): bool
    {
        return $callable instanceof \Closure;
    }

    abstract protected function getListenerEntry(callable $listener, string $type): ListenerEntry;
}
