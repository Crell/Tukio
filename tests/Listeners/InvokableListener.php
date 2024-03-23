<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;

class InvokableListener
{
    public function __invoke(CollectingEvent $event): void
    {
        $event->add(static::class);
    }
}
