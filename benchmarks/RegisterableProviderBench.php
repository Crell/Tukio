<?php
declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;

use Crell\Tukio\CollectingEvent;
use Crell\Tukio\OrderedListenerProvider;
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
        $this->provider = new OrderedListenerProvider();

        $priority = new \InfiniteIterator(new \ArrayIterator(static::$listenerPriorities));
        $priority->next();

        foreach(range(1, static::$numListeners) as $counter) {
            $this->provider->addListener(function(CollectingEvent $task) {}, $priority->current());
            $priority->next();
        }
    }
}
