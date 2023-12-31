<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\ListenerBefore;
use Crell\Tukio\ListenerPriority;

class TestAttributedListeners
{
    #[ListenerPriority(id: 'a', priority: -4)]
    public static function listenerA(CollectingEvent $event): void
    {
        $event->add('A');
    }

    #[ListenerBefore(before: 'a')]
    public static function listenerB(CollectingEvent $event): void
    {
        $event->add('B');
    }

    #[ListenerPriority(id: 'c', priority: -4)]
    public function listenerC(CollectingEvent $event): void
    {
        $event->add('C');
    }

    #[ListenerBefore(before: 'c')]
    public function listenerD(CollectingEvent $event): void
    {
        $event->add('D');
    }
}
