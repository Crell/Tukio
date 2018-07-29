<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Container\ContainerInterface;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class RegisterableListenerProvider implements ListenerProviderInterface
{
    use ParameterDeriverTrait;

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


    public function getListenersForEvent(EventInterface $event): iterable
    {
        /** @var ListenerEntry $listener */
        foreach ($this->listeners as $listener) {
            if ($event instanceof $listener->type) {
                yield $listener->listener;
            }
        }
    }

    /**
     * Adds a listener to the provider.
     *
     * @param callable $listener
     *   The listener to register.
     * @param int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @param string|null $type
     *   The class or interface type of events for which this listener will be registered. If not provided
     *   it will be derived based on the type hint of the listener.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListener(callable $listener, $priority = 0, string $type = null): string
    {
        $type = $type ?? $this->getParameterType($listener);

        return $this->listeners->addItem(new ListenerEntry($listener, $type), $priority);
    }

    /**
     * Adds a listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param callable $listener
     *   The listener to register.
     * @param string|null $type
     *   The class or interface type of events for which this listener will be registered. If not provided
     *   it will be derived based on the type hint of the listener.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerBefore(string $pivotId, callable $listener, string $type = null) : string
    {
        $type = $type ?? $this->getParameterType($listener);

        return $this->listeners->addItemBefore($pivotId, new ListenerEntry($listener, $type));
    }

    /**
     * Adds a listener to trigger after another existing listener.
     *
     * Note: The new listener is only guaranteed to come after the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param callable $listener
     *   The listener to register.
     * @param string|null $type
     *   The class or interface type of events for which this listener will be registered. If not provided
     *   it will be derived based on the type hint of the listener.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerAfter(string $pivotId, callable $listener, string $type = null) : string
    {
        $type = $type ?? $this->getParameterType($listener);

        return $this->listeners->addItemAfter($pivotId, new ListenerEntry($listener, $type));
    }

    /**
     * Adds a method on a service as a listener.
     *
     * @param string $serviceName
     *   The name of a service on which this listener lives.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param string|null $type
     *   The class or interface type of events for which this listener will be registered.
     * @param int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerService(string $serviceName, string $methodName, string $type, $priority = 0): string
    {
        return $this->addListener($this->makeListenerForService($serviceName, $methodName), $priority, $type);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param string $serviceName
     *   The name of a service on which this listener lives.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerServiceBefore(string $pivotId, string $serviceName, string $methodName, string $type) : string
    {
        return $this->addListenerBefore($pivotId, $this->makeListenerForService($serviceName, $methodName), $type);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param string $serviceName
     *   The name of a service on which this listener lives.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerServiceAfter(string $pivotId, string $serviceName, string $methodName, string $type) : string
    {
        return $this->addListenerAfter($pivotId, $this->makeListenerForService($serviceName, $methodName), $type);
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
        // the wrapping listener must listen to just EventInterface.  The explicit $type means it will still get only
        // the right event type, and the real listener can still type itself properly.
        $container = $this->container;
        $listener = function(EventInterface $event) use ($serviceName, $methodName, $container) : void {
            $container->get($serviceName)->$methodName($event);
        };
        return $listener;
    }
}


class ListenerEntry
{
    /** @var callable */
    public $listener;

    /** @var string */
    public $type;

    public function __construct(callable $listener, string $type)
    {
        $this->listener = $listener;
        $this->type = $type;
    }
}
