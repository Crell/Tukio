<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;

class ListenService
{
    public static function listen(CollectingEvent $event): void
    {
        $event->add('D');
    }
}
