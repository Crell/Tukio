<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Entry\ListenerEntry;
use Crell\Tukio\Entry\ListenerFunctionEntry;
use Crell\Tukio\Entry\ListenerServiceEntry;
use Crell\Tukio\Entry\ListenerStaticMethodEntry;

class ProviderBuilder extends ProviderCollector implements \IteratorAggregate
{
    /**
     * @var array<class-string>
     */
    protected array $optimizedEvents = [];

    /**
     * Pre-specify an event class that should have an optimized listener list built.
     *
     * @param class-string ...$events
     */
    public function optimizeEvents(string ...$events): void
    {
        $this->optimizedEvents = [...$this->optimizedEvents, ...$events];
    }

    /**
     * @return array<class-string>
     */
    public function getOptimizedEvents(): array
    {
        return $this->optimizedEvents;
    }

    public function listenerService(
        string $service,
        string $method,
        string $type,
        ?int $priority = null,
        array $before = [],
        array $after = [],
        ?string $id = null
    ): string
    {
        $entry = new ListenerServiceEntry($service, $method, $type);
        $id ??= $service . '-' . $method;

        return $this->listeners->add($entry, $id, priority: $priority, before: $before, after: $after);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->listeners;
    }

    protected function getListenerEntry(callable $listener, string $type): ListenerEntry
    {
        // We can't serialize a closure.
        if ($listener instanceof \Closure) {
            throw new \InvalidArgumentException('Closures cannot be used in a compiled listener provider.');
        }
        // String means it's a function name, and that's safe.
        if (is_string($listener)) {
            return new ListenerFunctionEntry($listener, $type);
        }
        // This is how we recognize a static method call.
        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return new ListenerStaticMethodEntry($listener[0], $listener[1], $type);
        }
        // Anything else isn't safe to serialize, so reject it.
        throw new \InvalidArgumentException('That callable type cannot be used in a compiled listener provider.');
    }
}
