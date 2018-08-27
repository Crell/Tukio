<?php
declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingTask;
use Crell\Tukio\RegisterableListenerProvider;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @Groups({"Providers"})
 */
class RegisterableProviderBench extends ProviderBenchBase
{
    /**
     * @var ListenerProviderInterface
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = new RegisterableListenerProvider();

        $priority = new \InfiniteIterator(new \ArrayIterator(static::$listenerPriorities));
        $priority->next();

        foreach(range(1, static::$numListeners) as $counter) {
            $this->provider->addListener(function(CollectingTask $task) {}, $priority->current());
            $priority->next();
        }
    }
}
