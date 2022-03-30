<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;

#[ListenerPriority(0, 'a')]
// @phpstan-ignore-next-line
#[JunkAttribute]
function at_listener_one(CollectingEvent $event): void
{
    $event->add('A');
}

#[ListenerBefore('a', 'b')]
function at_listener_two(CollectingEvent $event): void
{
    $event->add('B');
}

#[ListenerPriority(id: 'c', priority: 4)]
function at_listener_three(CollectingEvent $event): void
{
    $event->add('C');
}

#[ListenerPriority(id: 'd', priority: 2, type: CollectingEvent::class)]
function at_listener_four($event): void
{
    $event->add('D');
}

class DoNothingEvent
{
    public $called = false;
}

class TestAttributedListeners
{
    #[ListenerPriority(id: 'a', priority: -4)]
    public static function listenerA(CollectingEvent $event) : void
    {
        $event->add('A');
    }

    #[ListenerBefore(before: 'a')]
    public static function listenerB(CollectingEvent $event) : void
    {
        $event->add('B');
    }

    #[ListenerPriority(id: 'c', priority: -4)]
    public function listenerC(CollectingEvent $event) : void
    {
        $event->add('C');
    }

    #[ListenerBefore(before: 'c')]
    public function listenerD(CollectingEvent $event) : void
    {
        $event->add('D');
    }
}

#[Listener('A')]
#[Listener('B')]
#[Listener('C')]
function at_multi_one(CollectingEvent $event): void
{
    $event->add('A');
}

/**
 * @requires PHP >= 8.0
 */
class OrderedListenerProviderAttributeTest extends TestCase
{
    public function test_id_from_attribute_is_found() : void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $id_one = $p->addListener("{$ns}at_listener_one", -4);
        $p->addListener("{$ns}at_listener_two");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('a', $id_one);
        $this->assertEquals('BA', implode($event->result()));
    }

    public function test_priority_from_attribute_honored() : void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->addListener("{$ns}at_listener_one", 0);
        $p->addListener("{$ns}at_listener_three");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CA', implode($event->result()));
    }

    public function test_type_from_attribute_called_correctly() : void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->addListener("{$ns}at_listener_one", 0);
        $p->addListener("{$ns}at_listener_three");
        $p->addListener("{$ns}at_listener_four");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CDA', implode($event->result()));
    }

    public function test_type_from_attribute_skips_correctly() : void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->addListener("{$ns}at_listener_four");

        $event = new DoNothingEvent();

        // This should explode with an "method not found" error
        // if the event is passed to the listener.
        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals(false, $event->called);
    }

    public function test_attributes_found_on_object_methods() : void
    {
        $p = new OrderedListenerProvider();

        $object = new TestAttributedListeners();

        $p->addListener([$object, 'listenerC']);
        $p->addListener([$object, 'listenerD']);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('DC', implode($event->result()));
    }

    public function test_before_after_methods_win_over_attributes(): void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $idOne = $p->addListener("{$ns}at_listener_one", 0);
        $idTwo = $p->addListenerBefore($idOne, "{$ns}at_listener_three");
        $p->addListenerAfter($idTwo, "{$ns}at_listener_four");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CAD', implode($event->result()));
    }

    public function test_multiple_attributes_read_separately(): void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $idOne = $p->addListener("{$ns}at_multi_one");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('AAA', implode($event->result()));
    }
}
