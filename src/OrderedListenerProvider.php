<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class OrderedListenerProvider extends ProviderCollector implements ListenerProviderInterface
{
    public function __construct(protected ?ContainerInterface $container = null)
    {
        parent::__construct();
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

    protected function getListenerEntry(callable $listener, string $type): ListenerEntry
    {
        return new ListenerEntry($listener, $type);
    }

    public function listenerService(
        string $service,
        ?string $method = null,
        ?string $type = null,
        ?int $priority = null,
        array $before = [],
        array $after = [],
        ?string $id = null
    ): string {
        $method ??= $this->deriveMethod($service);

        if (!$type) {
            if (!class_exists($service)) {
                throw ServiceRegistrationClassNotExists::create($service);
            }
            // @phpstan-ignore-next-line
            $type = $this->getParameterType([$service, $method]);
        }

        $id ??= $service . '-' . $method;
        return $this->listener($this->makeListenerForService($service, $method), priority: $priority, before: $before, after: $after, id: $id, type: $type);
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
