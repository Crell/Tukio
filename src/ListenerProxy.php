<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

class ListenerProxy
{
    use ParameterDeriverTrait;

    protected OrderedProviderInterface $provider;

    protected string $serviceName;

    /**
     * @var class-string
     */
    protected string $serviceClass;

    /**
     * @var array<string>
     *     Methods that have already been registered on this subscriber, so we know not to double-subscribe them.
     */
    protected array $registeredMethods = [];

    /**
     * @param OrderedProviderInterface $provider
     * @param string $serviceName
     * @param class-string $serviceClass
     */
    public function __construct(OrderedProviderInterface $provider, string $serviceName, string $serviceClass)
    {
        $this->provider = $provider;
        $this->serviceName = $serviceName;
        $this->serviceClass = $serviceClass;
    }

    /**
     * Adds a method on a service as a listener.
     *
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param ?int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @param ?string $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListener(string $methodName, ?int $priority = 0, ?string $id = null, ?string $type = null): string
    {
        $type = $type ?? $this->getServiceMethodType($methodName);
        $this->registeredMethods[] = $methodName;
        return $this->provider->addListenerService($this->serviceName, $methodName, $type, $priority, $id);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $before
     *   The ID of an existing listener.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param ?string $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerBefore(string $before, string $methodName, ?string $id = null, ?string $type = null): string
    {
        $type = $type ?? $this->getServiceMethodType($methodName);
        $this->registeredMethods[] = $methodName;
        return $this->provider->addListenerServiceBefore($before, $this->serviceName, $methodName, $type, $id);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $after
     *   The ID of an existing listener.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param ?string $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerAfter(string $after, string $methodName, ?string $id = null, ?string $type = null): string
    {
        $type = $type ?? $this->getServiceMethodType($methodName);
        $this->registeredMethods[] = $methodName;
        return $this->provider->addListenerServiceAfter($after, $this->serviceName, $methodName, $type, $id);
    }

    /**
     * @return array<string>
     */
    public function getRegisteredMethods(): array
    {
        return $this->registeredMethods;
    }

    /**
     * Safely gets the required Type for a given method from the service class.
     *
     * @param string $methodName
     *   The method name of the listener being registered.
     *
     * @return string
     *   The type required by the listener.
     *
     * @throws InvalidTypeException
     *   If the method has invalid type-hinting, throws an error with a service/method trace.
     */
    protected function getServiceMethodType(string $methodName): string
    {
        try {
            // We don't have a real object here, so we cannot use first-class-closures.
            // PHPStan complains that an aray is not a callable, even though it is, because PHP.
            // @phpstan-ignore-next-line
            $type = $this->getParameterType([$this->serviceClass, $methodName]);
        } catch (\InvalidArgumentException $exception) {
            throw InvalidTypeException::fromClassCallable($this->serviceClass, $methodName, $exception);
        }
        return $type;
    }
}
