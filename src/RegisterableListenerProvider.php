<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class RegisterableListenerProvider implements ListenerProviderInterface
{
    use ParameterDeriverTrait;

    /**
     * @var OrderedCollection
     */
    protected $listeners;

    public function __construct()
    {
        $this->listeners = new OrderedCollection();
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
