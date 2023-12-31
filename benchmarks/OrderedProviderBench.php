<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\OrderedListenerProvider;
use PhpBench\Benchmark\Metadata\Annotations\Groups;

/**
 * @Groups({"Providers"})
 */
class OrderedProviderBench extends ProviderBenchBase
{
    public function setUp(): void
    {
        $this->provider = new OrderedListenerProvider();

        $priority = new \InfiniteIterator(new \ArrayIterator(static::$listenerPriorities));
        $priority->next();

        foreach(range(1, static::$numListeners) as $counter) {
            $this->provider->addListener([static::class, 'fakeListener'], $priority->current());
            $priority->next();
        }
    }
}
