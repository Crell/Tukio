<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class DelegatingProviderTest extends TestCase
{

    public function test_dedicated_provider_blocks_default() : void
    {
        $specific = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (CollectingTask $event) { $event->add('A'); };
            }
        };
        $default = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (CollectingTask $event) { $event->add('B'); };
            }
        };

        $p = new DelegatingProvider();
        $p->setDefaultProvider($default);
        $p->addProvider($specific, [CollectingTask::class]);

        $event = new CollectingTask();
        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
        $this->assertEquals('A', implode($event->result()));
    }

    public function test_dedicated_provider_unused_goes_to_default() : void
    {
        $specific = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                return [];
            }
        };
        $default = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (CollectingTask $event) { $event->add('B'); };
            }
        };

        $p = new DelegatingProvider();
        $p->setDefaultProvider($default);
        $p->addProvider($specific, [DoesNotExist::class]);

        $event = new CollectingTask();
        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
        $this->assertEquals('B', implode($event->result()));
    }


}
