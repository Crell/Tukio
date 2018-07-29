<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

class RegisterableListenerProviderTest extends TestCase
{

    public function test_add_ordered_listeners() : void
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

    public function test_add_listener_before() : void
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

    public function test_add_listener_after() : void
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
