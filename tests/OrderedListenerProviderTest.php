<?php

declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EventOne extends CollectingEvent {}

class EventTwo extends CollectingEvent {}

class OrderedListenerProviderTest extends TestCase
{
    #[Test]
    public function only_type_correct_listeners_are_returned(): void
    {
        $p = new OrderedListenerProvider();

        $p->addListener(function (EventOne $event) {
            $event->add('Y');
        });
        $p->addListener(function (CollectingEvent $event) {
            $event->add('Y');
        });
        $p->addListener(function (EventTwo $event) {
            $event->add('N');
        });
        // This class doesn't exist but should not result in an error.
        // @phpstan-ignore-next-line
        $p->addListener(function (NoEvent $event) {
            // @phpstan-ignore-next-line
            $event->add('F');
        });

        $event = new EventOne();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('YY', implode($event->result()));
    }

    #[Test]
    public function add_ordered_listeners(): void
    {
        $p = new OrderedListenerProvider();

        $p->addListener(function (CollectingEvent $event) {
            $event->add('E');
        }, 0);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('C');
        }, 100);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function add_listener_before(): void
    {
        $p = new OrderedListenerProvider();

        $p->addListener(function (CollectingEvent $event) {
            $event->add('A');
        }, 0, id: 'A');
        $bid = $p->addListener(function (CollectingEvent $event) {
            $event->add('B');
        }, 90, id: 'B');
        $p->addListener(function (CollectingEvent $event) {
            $event->add('C');
        }, -5, id: 'C');
        $p->addListenerBefore($bid, function (CollectingEvent $event) {
            $event->add('D');
        }, id: 'D');
        $p->addListener(function (CollectingEvent $event) {
            $event->add('E');
        }, 0, id: 'E');

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = implode($event->result());
        self::assertTrue(strpos($result, 'B') < strpos($result, 'C'));
        self::assertTrue(strpos($result, 'D') < strpos($result, 'B'));
        self::assertTrue(strpos($result, 'B') < strpos($result, 'E'));
        self::assertTrue(strpos($result, 'E') < strpos($result, 'C'));

//        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function add_listener_after(): void
    {
        $p = new OrderedListenerProvider();

        $rid = $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, -5);
        $p->addListenerBefore($rid, function (CollectingEvent $event) {
            $event->add('C');
        });
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListenerAfter($rid, function (CollectingEvent $event) {
            $event->add('E');
        });

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function add_malformed_listener(): void
    {
        $this->expectException(InvalidTypeException::class);

        $p = new OrderedListenerProvider();

        $p->addListener(function ($event) {
            $event->add('A');
        });
    }

    #[Test]
    public function add_malformed_listener_before(): void
    {
        $this->expectException(InvalidTypeException::class);

        $p = new OrderedListenerProvider();

        $a = $p->addListener(function (CollectingEvent $event) {
            $event->add('A');
        });
        $p->addListenerBefore($a, function ($event) {
            $event->add('B');
        });
    }

    #[Test]
    public function add_malformed_listener_after(): void
    {
        $this->expectException(InvalidTypeException::class);

        $p = new OrderedListenerProvider();

        $a = $p->addListener(function (CollectingEvent $event) {
            $event->add('A');
        });
        $p->addListenerAfter($a, function ($event) {
            $event->add('B');
        });
    }
}
