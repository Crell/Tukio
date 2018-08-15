<?php
declare(strict_types=1);

namespace Crell\Tukio;


class MockSubscriber
{
    public function onA(CollectingTask $event) : void
    {
        $event->add('A');
    }
    public function onB(CollectingTask $event) : void
    {
        $event->add('B');
    }
    public function onC(CollectingTask $event) : void
    {
        $event->add('C');
    }
    public function onD(CollectingTask $event) : void
    {
        $event->add('D');
    }
    public function onE(CollectingTask $event) : void
    {
        $event->add('E');
    }

    public function onF(NoEvent $event) : void
    {
        $event->add('F');
    }

    /*
    public static function getSubscribers(): iterable
    {
        return [
            ['method' => 'a', 'type' => CollectingEvent::class, 'priority' => 10],  // Specify everything.
            ['method' => 'b', 'priority' => 9], // Both type and prioirty can be omitted.
            'd',  // Just list the method, the rest is default/autodetected. The most common case.
            ['method' => 'c', 'type' => CollectingEvent::class], // Both type and prioirty can be omitted.
            ['e' => -5], // You can short-case the method/priority, but not the type. Use the full version for that.
            'f', // This one shouldn't fire.
        ];
    }
    */
}

