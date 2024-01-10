<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\FuncAnalyzer;
use Crell\AttributeUtils\FunctionAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\OrderedCollection\MultiOrderedCollection;
use Crell\Tukio\Entry\ListenerEntry;
use Fig\EventDispatcher\ParameterDeriverTrait;

abstract class ProviderCollector implements OrderedProviderInterface
{
    use ParameterDeriverTrait;

    /**
     * @var MultiOrderedCollection<ListenerEntry>
     */
    protected MultiOrderedCollection $listeners;

    public function __construct(
        protected readonly FunctionAnalyzer $funcAnalyzer = new FuncAnalyzer(),
        protected readonly ClassAnalyzer $classAnalyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {
        $this->listeners = new MultiOrderedCollection();
    }

    public function listener(
        callable $listener,
        ?int $priority = null,
        array $before = [],
        array $after = [],
        ?string $id = null,
        ?string $type = null
    ): string {
        $orderSpecified = !is_null($priority) || !empty($before) || !empty($after);

        if (!$orderSpecified || !$type || !$id) {
            /** @var Listener $def */
            $def = $this->getAttributeDefinition($listener);
            $id ??= $def?->id ?? $this->getListenerId($listener);
            $type ??= $def?->type ?? $this->getType($listener);

            // If any ordering is specified explicitly, that completely overrules any
            // attributes.
            if (!$orderSpecified) {
                $priority = $def->priority;
                $before = $def->before;
                $after = $def->after;
            }
        }

        $entry = $this->getListenerEntry($listener, $type);

        return $this->listeners->add(
            item: $entry,
            id: $id,
            priority: $priority,
            before: $before,
            after: $after
        );
    }

    public function addListener(callable $listener, ?int $priority = null, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, priority: $priority, id: $id, type: $type);
    }

    public function addListenerBefore(string $before, callable $listener, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener,  before: [$before], id: $id, type: $type);
    }

    public function addListenerAfter(string $after, callable $listener, ?string $id = null, ?string $type = null): string
    {
        return $this->listener($listener, after: [$after], id: $id, type: $type);
    }

    public function addListenerService(string $service, string $method, string $type, ?int $priority = null, ?string $id = null): string
    {
        return $this->listenerService($service, $method, $type, priority: $priority, id: $id);
    }

    public function addListenerServiceBefore(string $before, string $service, string $method, string $type, ?string $id = null): string
    {
        return $this->listenerService($service, $method, $type, before: [$before], id: $id);
    }

    public function addListenerServiceAfter(string $after, string $service, string $method, string $type, ?string $id = null): string
    {
        return $this->listenerService($service, $method, $type, after: [$after], id: $id);
    }

    public function addSubscriber(string $class, ?string $service = null): void
    {
        $service ??= $class;

        // First allow manual registration through the proxy object.
        // This is deprecated.  Please don't use it.
        $proxy = $this->addSubscribersByProxy($class, $service);

        $proxyRegisteredMethods = $proxy->getRegisteredMethods();

        try {
            // Get all methods on the class, via AttributeUtils to handle reflection and caching.
            $methods = $this->classAnalyzer->analyze($class, Listener::class)->methods;

            /**
             * @var string $methodName
             * @var Listener $def
             */
            foreach ($methods as $methodName => $def) {
                if (in_array($methodName, $proxyRegisteredMethods, true)) {
                    // Exclude anything already registered by proxy.
                    continue;
                }
                // If there was an attribute-based definition, that takes priority.
                if ($def->hasDefinition) {
                    $this->listenerService($service, $methodName, $def->type, $def->priority, $def->before,$def->after, $def->id);
                } elseif (str_starts_with($methodName, 'on') && $def->paramCount === 1) {
                    // Try to register it iff the method starts with "on" and has only one required parameter.
                    // (More than one required parameter is guaranteed to fail when invoked.)
                    if (!$def->type) {
                        throw InvalidTypeException::fromClassCallable($class, $methodName);
                    }
                    $this->listenerService($service, $methodName, type: $def->type, id: $service . '-' . $methodName);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
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
     * @param callable|array{0: class-string, 1: string}|array{0: object, 1: string} $listener
     */
    protected function getAttributeDefinition(callable|array $listener): Listener
    {
        if ($this->isFunctionCallable($listener) || $this->isClosureCallable($listener)) {
            /** @var \Closure|string $listener */
            return $this->funcAnalyzer->analyze($listener, Listener::class);
        }

        if ($this->isObjectCallable($listener)) {
            /** @var array{0: object, 1: string} $listener */
            [$object, $method] = $listener;

            $def = $this->classAnalyzer->analyze($object::class, Listener::class);
            return $def->methods[$method];
        }

        /** @var array{0: class-string, 1: string} $listener */
        if ($this->isClassCallable($listener)) {
            /** @var array{0: class-string, 1: string} $listener */
            [$class, $method] = $listener;

            $def = $this->classAnalyzer->analyze($class, Listener::class);
            return $def->staticMethods[$method];
        }

        return new Listener();
    }

    protected function deriveMethod(string $service): string
    {
        if (!class_exists($service)) {
            throw ServiceRegistrationClassNotExists::create($service);
        }
        $rClass = new \ReflectionClass($service);
        $rMethods = $rClass->getMethods();

        // If the class has only one method, assume that's the listener.
        // Otherwise, use __invoke if not otherwise specified.
        // Otherwise, we cannot tell what to do so throw.
        return match (true) {
            count($rMethods) === 1 => $rMethods[0]->name,
            $rClass->hasMethod('__invoke') => '__invoke',
            default => throw ServiceRegistrationTooManyMethods::create($service),
        };
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
     * @param callable|array{0: class-string, 1: string}|array{0: object, 1: string} $listener
     *   The listener for which to derive an ID.
     *
     * @return string|null
     *   The derived ID if possible or null if no reasonable ID could be derived.
     */
    protected function getListenerId(callable|array $listener): ?string
    {
        if ($this->isFunctionCallable($listener)) {
            // Function callables are strings, so use that directly.
            /** @var string $listener */
            return $listener;
        }

        if ($this->isObjectCallable($listener)) {
            /** @var array{0: object, 1: string} $listener */
            return get_class($listener[0]) . '::' . $listener[1];
        }

        if ($this->isClassCallable($listener)) {
            /** @var array{0: class-string, 1: string} $listener */
            return $listener[0] . '::' . $listener[1];
        }

        // Anything else we can't derive an ID for logically.
        return null;
    }

    /**
     * Determines if a callable represents a function.
     *
     * Or at least a reasonable approximation, since a function name may not be defined yet.
     *
     * @param callable|array<mixed, mixed> $callable
     * @return bool
     *  True if the callable represents a function, false otherwise.
     */
    protected function isFunctionCallable(callable|array $callable): bool
    {
        // We can't check for function_exists() because it may be included later by the time it matters.
        return is_string($callable);
    }

    /**
     * Determines if a callable represents a method on an object.
     *
     * @param callable|array<mixed, mixed> $callable
     * @return bool
     *  True if the callable represents a method object, false otherwise.
     */
    protected function isObjectCallable(callable|array $callable): bool
    {
        return is_array($callable) && is_object($callable[0]);
    }

    /**
     * Determines if a callable represents a closure/anonymous function.
     *
     * @param callable|array<mixed, mixed> $callable
     * @return bool
     *  True if the callable represents a closure object, false otherwise.
     */
    protected function isClosureCallable(callable|array $callable): bool
    {
        return $callable instanceof \Closure;
    }

    /**
     * Determines if a callable represents a static class method.
     *
     * The parameter here is untyped so that this method may be called with an
     * array that represents a class name and a non-static method.  The routine
     * to determine the parameter type is identical to a static method, but such
     * an array is still not technically callable.  Omitting the parameter type here
     * allows us to use this method to handle both cases.
     *
     * This method must therefore be called first above, as the array is not actually
     * an `is_callable()` and will fail `Closure::fromCallable()`.  Because PHP.
     *
     * @param callable|array<mixed, mixed> $callable
     * @return bool
     *   True if the callable represents a static method, false otherwise.
     */
    protected function isClassCallable($callable): bool
    {
        return is_array($callable) && is_string($callable[0]) && class_exists($callable[0]);
    }

    abstract protected function getListenerEntry(callable $listener, string $type): ListenerEntry;
}
