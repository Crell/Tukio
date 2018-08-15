<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\Annotations\Listener;
use Doctrine\Common\Annotations\Reader;
use Psr\Container\ContainerInterface;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class RegisterableListenerProvider implements ListenerProviderInterface, RegisterableProviderInterface
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

    /**
     * Doctrine annotations reader.
     *
     * This is optional. Annotation support is not enabled if it's not defined.
     *
     * @var Reader
     */
    protected $reader;

    public function __construct(ContainerInterface $container = null, Reader $reader = null)
    {
        $this->listeners = new OrderedCollection();
        $this->container = $container;
        $this->reader = $reader;
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

    public function addListener(callable $listener, $priority = 0, string $type = null): string
    {
        $type = $type ?? $this->getParameterType($listener);

        if ($this->reader && $this->isClassCallable($listener)) {
            /** @var Listener $annotation */

            //new \ReflectionClass($listener[0]);
            $reflection = new \ReflectionMethod($listener[0], $listener[1]);

            $annotation = $this->reader->getMethodAnnotation($reflection, Listener::class);
            if ($annotation) {
                $type = $annotation->type ?? $type;
                $id = $annotation->id ?? $this->getProposedId($listener);
                $entry = new ListenerEntry($listener, $type);

                switch ($annotation->getRuleType()) {
                    case 'before':
                        return $this->listeners->addItemBefore($annotation->before, $entry, $id);
                        break;
                    case 'after':
                        return $this->listeners->addItemAfter($annotation->before, $entry, $id);
                        break;
                    case 'priority':
                        return $this->listeners->addItem($entry, $annotation->priority, $id);
                        break;
                }
            }
        }

        $id = $this->getProposedId($listener);

        return $this->listeners->addItem(new ListenerEntry($listener, $type), $priority, $id);
    }


    /**
     * Attempts to suggest a reasonably predictable ID for a given callable.
     *
     * @param callable $listener
     *   The callable for which to derive an ID.
     * @return null|string
     *   A recommended ID for the callable, or null if no reasonable suggestion could be made.
     */
    protected function getProposedId(callable $listener) : ?string
    {
        if ($this->isFunctionCallable($listener)) {
            /** @var string $listener */
            return $listener;
        }
        elseif ($this->isClassCallable($listener)) {
            return $listener[0] . '::' . $listener[1];
        }
        elseif ($this->isObjectCallable($listener)) {
            return get_class($listener[0]) . '-' . $listener[1];
        }

        // There's no educated guess we can make about the ID.
        return null;
    }

    public function addListenerBefore(string $pivotId, callable $listener, string $type = null) : string
    {
        $type = $type ?? $this->getParameterType($listener);

        return $this->listeners->addItemBefore($pivotId, new ListenerEntry($listener, $type));
    }

    public function addListenerAfter(string $pivotId, callable $listener, string $type = null) : string
    {
        $type = $type ?? $this->getParameterType($listener);

        return $this->listeners->addItemAfter($pivotId, new ListenerEntry($listener, $type));
    }

    public function addListenerService(string $serviceName, string $methodName, string $type, $priority = 0): string
    {
        return $this->addListener($this->makeListenerForService($serviceName, $methodName), $priority, $type);
    }

    public function addListenerServiceBefore(string $pivotId, string $serviceName, string $methodName, string $type) : string
    {
        return $this->addListenerBefore($pivotId, $this->makeListenerForService($serviceName, $methodName), $type);
    }

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

    public function addSubscriber(string $class, string $serviceName) : void
    {
        try {
            $rClass = new \ReflectionClass($class);
            $methods = $rClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                if (strpos($rMethod->getName(), 'on') !== false) {
                    $params = $rMethod->getParameters();
                    $type = $params[0]->getType()->getName();
                    $this->addListenerService($serviceName, $rMethod->getName(), $type);
                }
            }
        }
        catch (\ReflectionException $e) {
            throw new \RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }
}
