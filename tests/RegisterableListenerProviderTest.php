<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

class EventOne extends CollectingEvent {}

class EventTwo extends CollectingEvent {}

class RegisterableListenerProviderTest extends TestCase
{


    public function test_only_type_correct_listeners_are_returned(): void
    {
        $p = new RegisterableListenerProvider();

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
        $p->addListener(function (NoEvent $event) {
            $event->add('F');
        });

        $event = new EventOne();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('YY', implode($event->result()));
    }

    public function test_add_ordered_listeners(): void
    {
        $p = new RegisterableListenerProvider();

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

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_before(): void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener(function (CollectingEvent $event) {
            $event->add('E');
        }, 0);
        $rid = $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListenerBefore($rid, function (CollectingEvent $event) {
            $event->add('C');
        });
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_after(): void
    {
        $p = new RegisterableListenerProvider();

        $rid = $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
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

        $this->assertEquals('CRELL', implode($event->result()));
    }
}
