<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;

class ArbitraryListener
{
    public function doStuff(CollectingEvent $event): void
    {
        $event->add(static::class);
    }
}
