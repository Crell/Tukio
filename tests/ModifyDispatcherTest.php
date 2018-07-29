<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;

class ModifyDispatcherTest extends TestCase
{

    public function test_dispatcher_calls_all_listeners() : void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (CollectingEvent $event) { $event->add('C'); };
                yield function (CollectingEvent $event) { $event->add('R'); };
                yield function (CollectingEvent $event) { $event->add('E'); };
                yield function (CollectingEvent $event) { $event->add('L'); };
                yield function (CollectingEvent $event) { $event->add('L'); };
            }
        };

        $d = new BasicModifyDispatcher($provider);

        $e = new CollectingEvent();
        $d->modify($e);

        $this->assertEquals('CRELL', implode($e->result()));
    }

    public function test_stoppable_events_stop() : void {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (StoppableCollectingEvent $event) { $event->add('C'); };
                yield function (StoppableCollectingEvent $event) { $event->add('R'); };
                yield function (StoppableCollectingEvent $event) { $event->add('E'); $event->stopPropagation(); };
                yield function (StoppableCollectingEvent $event) { $event->add('L'); };
                yield function (StoppableCollectingEvent $event) { $event->add('L'); };
            }
        };

        $d = new BasicModifyDispatcher($provider);

        $e = new StoppableCollectingEvent();
        $d->modify($e);

        $this->assertEquals('CRE', implode($e->result()));
    }
}
