<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;


class NotifyDispatcherTest extends TestCase
{
    protected $counter;

    /** @var ListenerProviderInterface */
    protected $provider;

    public function setUp()
    {
        parent::setUp();

        // This counter object lets us track which listeners were called without violating the
        // immutability of the event object.
        $this->counter = new class {
            protected $counts = [];
            public function inc(string $key) {
                $this->counts[$key] = isset($this->counts[$key]) ? $this->counts[$key] + 1 : 1;
            }

            public function countOf(string $key) {
                return isset($this->counts[$key]) ? $this->counts[$key] : 0;
            }
        };

        $this->provider = new class implements ListenerProviderInterface {
            /** @var array */
            protected $listeners;

            public function addListener($listener) : void
            {
                $this->listeners[] = $listener;
            }

            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield from $this->listeners;
            }
        };

    }

    public function test_dispatcher_calls_all_listeners() : void
    {
        $counter = $this->counter;

        $this->provider->addListener(function (BasicEvent $e) use ($counter) {
            $counter->inc('A');
        });

        // This should fire once.
        $this->provider->addListener(function (BasicEvent $e) use ($counter) {
            $counter->inc('B');
        });

        $d = new NotifyDispatcher($this->provider);

        $d->notify(new BasicEvent());

        $this->assertEquals(1, $this->counter->countOf('A'));
        $this->assertEquals(1, $this->counter->countOf('B'));
    }
}
