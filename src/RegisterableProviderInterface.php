<?php
declare(strict_types=1);

namespace Crell\Tukio;

interface RegisterableProviderInterface
{
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
    public function addListener(callable $listener, $priority = 0, string $type = null): string;

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
    public function addListenerBefore(string $pivotId, callable $listener, string $type = null): string;

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
    public function addListenerAfter(string $pivotId, callable $listener, string $type = null): string;

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
    public function addListenerService(string $serviceName, string $methodName, string $type, $priority = 0): string;

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
    public function addListenerServiceBefore(
        string $pivotId,
        string $serviceName,
        string $methodName,
        string $type
    ): string;

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
    public function addListenerServiceAfter(string $pivotId, string $serviceName, string $methodName, string $type) : string;

    /**
     * Registers all listener methods on a service as listeners.
     *
     * A method on the specified class is a listener if:
     * - It is public.
     * - It's name is in the form on*.  onUpdate(), onUserLogin(), onHammerTime() will all be registered.
     *
     * The event type the listener is for will be derived from the type hint in the method signature.
     *
     * @param string $class
     *   The class name to be registered as a subscriber.
     * @param string $serviceName
     *   The name of a service in the container.
     */
    public function addSubscriber(string $class, string $serviceName): void;
}
