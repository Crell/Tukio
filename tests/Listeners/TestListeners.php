<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;

class TestListeners
{
    public static function listenerA(CollectingEvent $event): void
    {
        $event->add('A');
    }

    public static function listenerB(CollectingEvent $event): void
    {
        $event->add('B');
    }

    public function listenerC(CollectingEvent $event): void
    {
        $event->add('C');
    }

    public function listenerD(CollectingEvent $event): void
    {
        $event->add('D');
    }
}
