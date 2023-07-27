<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingEvent;
use Crell\Tukio\DummyEvent;
use PhpBench\Benchmark\Metadata\Annotations\Groups;

/**
 * @Groups({"Providers"})
 */
class OptimizedCompiledProviderBench extends CompiledProviderBench
{
    protected static array $optimizeClasses = [CollectingEvent::class, DummyEvent::class];
}
