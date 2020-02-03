<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

class ListenerProxy
{
    use ParameterDeriverTrait;

    /** @var OrderedProviderInterface */
    protected $provider;

    /** @var string */
    protected $serviceName;

    /** @var string */
    protected $serviceClass;

    /** @var array */
    protected $registeredMethods = [];

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
     * @param int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @param string|null $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param string|null $type
     *   The class or interface type of events for which this listener will be registered.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListener(string $methodName, int $priority = 0, string $id = null, string $type = null): string
    {
        try {
            $type = $type ?? $this->getParameterType([$this->serviceClass, $methodName]);
        } catch (\InvalidArgumentException $e) {
            throw (new InvalidTypeException())->setMessageFromClass($this->serviceClass, $methodName);
        }
        $this->registeredMethods[] = $methodName;

        return $this->provider->addListenerService($this->serviceName, $methodName, $type, $priority, $id);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param string|null $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerBefore(string $pivotId, string $methodName, string $id = null, string $type = null): string
    {
        try {
            $type = $type ?? $this->getParameterType([$this->serviceClass, $methodName]);
        } catch (\InvalidArgumentException $e) {
            throw (new InvalidTypeException())->setMessageFromClass($this->serviceClass, $methodName);
        }
        $this->registeredMethods[] = $methodName;

        return $this->provider->addListenerServiceBefore($pivotId, $this->serviceName, $methodName, $type, $id);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param string|null $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerAfter(string $pivotId, string $methodName, string $id = null, string $type = null) : string
    {
        try {
            $type = $type ?? $this->getParameterType([$this->serviceClass, $methodName]);
        } catch (\InvalidArgumentException $e) {
            throw (new InvalidTypeException())->setMessageFromClass($this->serviceClass, $methodName);
        }
        $this->registeredMethods[] = $methodName;

        return $this->provider->addListenerServiceAfter($pivotId, $this->serviceName, $methodName, $type, $id);
    }

    public function getRegisteredMethods() : array
    {
        return $this->registeredMethods;
    }
}
