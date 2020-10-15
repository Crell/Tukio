<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Crell\Tukio\OrderedCollection\OrderedCollection;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class OrderedListenerProvider implements ListenerProviderInterface, OrderedProviderInterface
{
    use ProviderUtilitiesTrait;

    /**
     * @var OrderedCollection
     */
    protected $listeners;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->listeners = new OrderedCollection();
        $this->container = $container;
    }

    public function getListenersForEvent(object $event): iterable
    {
        /** @var ListenerEntry $listener */
        foreach ($this->listeners as $listener) {
            if ($event instanceof $listener->type) {
                yield $listener->listener;
            }
        }
    }

    /**
     * Tries to get the type of a callable listener.
     *
     * If unable, throws an exception with information about the listener whose type could not be fetched.
     *
     * @param callable $listener
     * @return string
     */
    protected function getType(callable $listener)
    {
        try {
            $type = $this->getParameterType($listener);
        } catch (\InvalidArgumentException $exception) {
            if ($this->isClassCallable($listener) || $this->isObjectCallable($listener)) {
                throw InvalidTypeException::fromClassCallable($listener[0], $listener[1], $exception);
            }
            if ($this->isFunctionCallable($listener) || $this->isClosureCallable($listener)) {
                throw InvalidTypeException::fromFunctionCallable($listener, $exception);
            }
            throw new InvalidTypeException($exception);
        }
        return $type;
    }

    public function addListener(callable $listener, int $priority = 0, string $id = null, string $type = null): string
    {
        if ($attributes = $this->getAttributes($listener)) {
            /** @var ListenerAttribute $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                if ($attrib instanceof ListenerBefore) {
                    $generatedId = $this->addListenerBefore($attrib->before, $listener, $id, $type);
                }
                else if ($attrib instanceof ListenerAfter) {
                    $generatedId = $this->addListenerAfter($attrib->after, $listener, $id, $type);
                }
                else {
                    $generatedId = $this->listeners->addItem(new ListenerEntry($listener, $type), $attrib->priority, $id);
                }
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItem(new ListenerEntry($listener, $type), $priority, $id);
    }

    public function addListenerBefore(string $before, callable $listener, string $id = null, string $type = null) : string
    {
        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemBefore($before, new ListenerEntry($listener, $type), $id);
    }

    public function addListenerAfter(string $after, callable $listener, string $id = null, string $type = null) : string
    {
        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemAfter($after, new ListenerEntry($listener, $type), $id);
    }

    public function addListenerService(string $service, string $method, string $type, int $priority = 0, string $id = null): string
    {
        $id = $id ?? $service . '-' . $method;
        return $this->addListener($this->makeListenerForService($service, $method), $priority, $id, $type);
    }

    public function addListenerServiceBefore(string $before, string $service, string $method, string $type, string $id = null) : string
    {
        $id = $id ?? $service . '-' . $method;
        return $this->addListenerBefore($before, $this->makeListenerForService($service, $method), $id, $type);
    }

    public function addListenerServiceAfter(string $after, string $service, string $method, string $type, string $id = null) : string
    {
        $id = $id ?? $service . '-' . $method;
        return $this->addListenerAfter($after, $this->makeListenerForService($service, $method), $id, $type);
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
    protected function makeListenerForService(string $serviceName, string $methodName) : callable
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
        $listener = function (object $event) use ($serviceName, $methodName, $container) : void {
            $container->get($serviceName)->$methodName($event);
        };
        return $listener;
    }

    public function addSubscriber(string $class, string $service) : void
    {
        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class))) {
            /** @var SubscriberInterface $class */
            $class::registerListeners($proxy);
        }

        try {
            $rClass = new \ReflectionClass($class);
            $methods = $rClass->getMethods(\ReflectionMethod::IS_PUBLIC);

            // Explicitly registered methods ignore all auto-registration mechanisms.
            $methods = array_filter($methods, fn(\ReflectionMethod $r) => !in_array($r->getName(), $proxy->getRegisteredMethods()));

            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                $methodName = $rMethod->getName();

                // This extra dance needed to keep the code working on PHP < 8.0. It can be removed once
                // 8.0 is made a requirement.
                $attributes = [];
                if (class_exists('ReflectionAttribute', false)) {
                    $attributes = array_map(fn(\ReflectionAttribute $attrib)
                        => $attrib->newInstance(), $rMethod->getAttributes(ListenerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF));
                }

                if (count($attributes)) {
                    /** @var ListenerAttribute $attrib */
                    foreach ($attributes as $attrib) {
                        $params = $rMethod->getParameters();
                        $paramType = $params[0]->getType();
                        // This can simplify to ?-> once we require PHP 8.0.
                        $type = $attrib->type ?? ($paramType ? $paramType->getName() : null);
                        if (is_null($type)) {
                            throw InvalidTypeException::fromClassCallable($class, $methodName);
                        }
                        if ($attrib instanceof ListenerBefore) {
                            $this->addListenerServiceBefore($attrib->before, $service, $methodName, $type, $attrib->id);
                        }
                        else if ($attrib instanceof ListenerAfter) {
                            $this->addListenerServiceAfter($attrib->after, $service, $methodName, $type, $attrib->id);
                        }
                        else {
                            $this->addListenerService($service, $methodName, $type, $attrib->priority, $attrib->id);
                        }
                    }
                }
                else if (strpos($methodName, 'on') === 0) {
                    $params = $rMethod->getParameters();
                    $type = $params[0]->getType();
                    if (is_null($type)) {
                        throw InvalidTypeException::fromClassCallable($class, $methodName);
                    }
                    $this->addListenerService($service, $rMethod->getName(), $type->getName());
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }
}
