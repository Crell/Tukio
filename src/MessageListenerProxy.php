<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

class MessageListenerProxy
{
    use ParameterDeriverTrait;

    /** @var RegisterableNotificationListenerProvider */
    protected $provider;

    /** @var string */
    protected $serviceName;

    /** @var string */
    protected $serviceClass;

    /** @var array */
    protected $registeredMethods = [];

    public function __construct(RegisterableNotificationListenerProvider $provider, string $serviceName, string $serviceClass)
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
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param string|null $type
     *   The class or interface type of events for which this listener will be registered.
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListener(string $methodName, string $type = null): void
    {
        $type = $type ?? $this->getParameterType([$this->serviceClass, $methodName]);
        $this->registeredMethods[] = $methodName;

        $this->provider->addListenerService($this->serviceName, $methodName, $type);
    }

    public function getRegisteredMethods() : array
    {
        return $this->registeredMethods;
    }
}
