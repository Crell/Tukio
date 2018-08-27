<?php
declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingTask;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @Groups({"Providers"})
 * @Revs(10000)
 * @Iterations(5)
 */
abstract class ProviderBenchBase extends TukioBenchmarks
{
    /**
     * @var ListenerProviderInterface
     */
    protected $provider;

    protected $numListeners = 1000;

    public function setUp()
    {
        throw new \Exception('You need to implement setUp().');
    }

    /**
     * ParamProviders({"provideItems"})
     */
    public function bench_match_provider() : void
    {
        $task = new CollectingTask();

        $listeners = $this->provider->getListenersForEvent($task);

        // Run out the generator.
        foreach ($listeners as $listener);
    }
}
