<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingEvent;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\OutputTimeUnit;
use PhpBench\Benchmark\Metadata\Annotations\RetryThreshold;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @Groups({"Providers"})
 * @Revs(50)
 * @Iterations(10)
 * @Warmup(2)
 * @OutputTimeUnit("milliseconds", precision=4)
 * @RetryThreshold(10.0)
 */
abstract class ProviderBenchBase extends TukioBenchmarks
{
    protected ListenerProviderInterface $provider;

    protected static int $numListeners = 1000;

    /**
     * @var array<int>
     *
     * Deliberately in an unsorted order to force it to need to be sorted.
     */
    protected static $listenerPriorities = [1, 2, -2, 3, 0, -1, -3];

    public function setUp(): void
    {
        throw new \Exception('You need to implement setUp().');
    }

    /**
     * ParamProviders({"provideItems"})
     */
    public function bench_match_provider(): void
    {
        $task = new CollectingEvent();

        $listeners = $this->provider->getListenersForEvent($task);

        // Run out the generator.
        is_array($listeners) || iterator_to_array($listeners);
    }

    public static function fakeListener(CollectingEvent $task): void
    {
    }
}
