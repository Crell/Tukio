<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Events\DummyEvent;
use PhpBench\Benchmark\Metadata\Annotations\Groups;

/**
 * @Groups({"Providers"})
 */
class OptimizedCompiledProviderBench extends CompiledProviderBench
{
    protected static array $optimizeClasses = [CollectingEvent::class, DummyEvent::class];
}
