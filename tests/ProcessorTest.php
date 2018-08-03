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
                yield function (CollectingTask $event) { $event->add('C'); };
                yield function (CollectingTask $event) { $event->add('R'); };
                yield function (CollectingTask $event) { $event->add('E'); };
                yield function (CollectingTask $event) { $event->add('L'); };
                yield function (CollectingTask $event) { $event->add('L'); };
            }
        };

        $d = new TaskProcessor($provider);

        $e = new CollectingTask();
        $d->modify($e);

        $this->assertEquals('CRELL', implode($e->result()));
    }

    public function test_stoppable_events_stop() : void {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (StoppableCollectingTask $event) { $event->add('C'); };
                yield function (StoppableCollectingTask $event) { $event->add('R'); };
                yield function (StoppableCollectingTask $event) { $event->add('E'); $event->stopPropagation(); };
                yield function (StoppableCollectingTask $event) { $event->add('L'); };
                yield function (StoppableCollectingTask $event) { $event->add('L'); };
            }
        };

        $d = new TaskProcessor($provider);

        $e = new StoppableCollectingTask();
        $d->process($e);

        $this->assertEquals('CRE', implode($e->result()));
    }
}
