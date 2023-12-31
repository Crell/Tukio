<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\Attributes\Test;
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

// @phpstan-ignore-next-line
#[ListenerPriority(id: 'd', priority: 2, type: CollectingEvent::class)]
function at_listener_four($event): void
{
    $event->add('D');
}

class DoNothingEvent
{
    public bool $called = false;
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

class OrderedListenerProviderAttributeTest extends TestCase
{
    #[Test]
    public function id_from_attribute_is_found() : void
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

        self::assertEquals('a', $id_one);
        self::assertEquals('BA', implode($event->result()));
    }

    #[Test]
    public function priority_from_attribute_honored() : void
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

        self::assertEquals('CA', implode($event->result()));
    }

    #[Test]
    public function type_from_attribute_called_correctly() : void
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

        self::assertEquals('CDA', implode($event->result()));
    }

    #[Test]
    public function type_from_attribute_skips_correctly() : void
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

        self::assertEquals(false, $event->called);
    }

    #[Test]
    public function attributes_found_on_object_methods() : void
    {
        $p = new OrderedListenerProvider();

        $object = new TestAttributedListeners();

        $p->addListener([$object, 'listenerC']);
        $p->addListener([$object, 'listenerD']);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('DC', implode($event->result()));
    }

    #[Test]
    public function before_after_methods_win_over_attributes(): void
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

        self::assertEquals('CAD', implode($event->result()));
    }

    #[Test]
    public function add_attribute_based_service_methods(): void
    {
        $container = new MockContainer();

        $container->addService(TestAttributedListeners::class, new TestAttributedListeners());

        $provider = new OrderedListenerProvider($container);

        $provider->listenerService(TestAttributedListeners::class, 'listenerC');
        $provider->listenerService(TestAttributedListeners::class, 'listenerD');

        $event = new CollectingEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('DC', implode($event->result()));
    }
}
