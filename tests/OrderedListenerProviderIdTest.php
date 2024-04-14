<?php

declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Fakes\MockContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

function event_listener_one(CollectingEvent $event): void
{
    $event->add('A');
}

function event_listener_two(CollectingEvent $event): void
{
    $event->add('B');
}

function event_listener_three(CollectingEvent $event): void
{
    $event->add('C');
}

function event_listener_four(CollectingEvent $event): void
{
    $event->add('D');
}


class OrderedListenerProviderIdTest extends TestCase
{

    #[Test]
    public function natural_id_for_function(): void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->addListener("{$ns}event_listener_one", -4);
        $p->addListenerBefore("{$ns}event_listener_one", "{$ns}event_listener_two");
        $p->addListenerAfter("{$ns}event_listener_two", "{$ns}event_listener_three");
        $p->addListenerAfter("{$ns}event_listener_three", "{$ns}event_listener_four");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('BACD', implode($event->result()));
    }

    #[Test]
    public function natural_id_for_static_method(): void
    {
        $p = new OrderedListenerProvider();

        $p->addListener([Listeners\TestListeners::class, 'listenerA'], -4);
        $p->addListenerBefore(Listeners\TestListeners::class . '::listenerA', [Listeners\TestListeners::class, 'listenerB']);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('BA', implode($event->result()));
    }

    #[Test]
    public function natural_id_for_object_method(): void
    {
        $p = new OrderedListenerProvider();

        $l = new Listeners\TestListeners();

        $p->addListener([$l, 'listenerC'], -4);
        $p->addListenerBefore(Listeners\TestListeners::class . '::listenerC', [$l, 'listenerD']);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('DC', implode($event->result()));
    }

    #[Test]
    public function explicit_id_for_function(): void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->addListener("{$ns}event_listener_one", -4, 'id-1');
        $p->addListenerBefore('id-1', "{$ns}event_listener_two", 'id-2');
        $p->addListenerAfter('id-2', "{$ns}event_listener_three", 'id-3');
        $p->addListenerAfter('id-3', "{$ns}event_listener_four");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('BACD', implode($event->result()));
    }

    #[Test]
    public function natural_id_for_service_listener(): void
    {
        $container = new MockContainer();

        $container->addService('A', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('A');
            }
        });
        $container->addService('B', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('B');
            }
        });

        $p = new OrderedListenerProvider($container);

        $idA = $p->addListenerService('A', 'listen', CollectingEvent::class, -4);
        $p->addListenerServiceAfter('A::listen', 'B', 'listen', CollectingEvent::class);

        $event = new CollectingEvent();
        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('A::listen', $idA);
        self::assertEquals('AB', implode($event->result()));
    }

}
