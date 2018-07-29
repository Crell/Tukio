<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

class RegisterableListenerProviderTest extends TestCase
{

    public function test_add_ordered_items() : void
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
}
