<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;

class Listen
{
    public static function listen(CollectingEvent $event): void
    {
        $event->add('C');
    }
}
