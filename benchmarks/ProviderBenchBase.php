<?php

declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingEvent;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @Groups({"Providers"})
 * @Revs(1000)
 * @Iterations(5)
 */
abstract class ProviderBenchBase extends TukioBenchmarks
{
    /**
     * @var ListenerProviderInterface
     */
    protected $provider;

    /** @var int */
    protected static $numListeners = 5000;

    /**
     * @var array
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
        iterator_to_array($listeners);
    }
}
