<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

function event_listener_one(CollectingEvent $event) : void
{
    $event->add('A');
}

function event_listener_two(CollectingEvent $event) : void
{
    $event->add('B');
}

function event_listener_three(CollectingEvent $event) : void
{
    $event->add('C');
}

function event_listener_four(CollectingEvent $event) : void
{
    $event->add('D');
}


class TestListeners
{
    public static function listenerA(CollectingEvent $event) : void
    {
        $event->add('A');
    }
    public static function listenerB(CollectingEvent $event) : void
    {
        $event->add('B');
    }

    public function listenerC(CollectingEvent $event) : void
    {
        $event->add('C');
    }

    public function listenerD(CollectingEvent $event) : void
    {
        $event->add('D');
    }
}

class OrderedListenerProviderIdTest extends TestCase
{

    public function test_natural_id_for_function() : void
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

        $this->assertEquals('BACD', implode($event->result()));
    }

    public function test_natural_id_for_static_method() : void
    {
        $p = new OrderedListenerProvider();

        $p->addListener([TestListeners::class, 'listenerA'], -4);
        $p->addListenerBefore(TestListeners::class . '::listenerA', [TestListeners::class, 'listenerB']);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BA', implode($event->result()));
    }

    public function test_natural_id_for_object_method() : void
    {
        $p = new OrderedListenerProvider();

        $l = new TestListeners();

        $p->addListener([$l, 'listenerC'], -4);
        $p->addListenerBefore(TestListeners::class . '::listenerC', [$l, 'listenerD']);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('DC', implode($event->result()));
    }

    public function test_explicit_id_for_function(): void
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

        $this->assertEquals('BACD', implode($event->result()));
    }

}
