<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;

class CompoundListener
{
    public function __invoke(CollectingEvent $event): void
    {
        $event->add(static::class);
    }

    public function dontUseThis(CollectingEvent $event): void
    {
        throw new \Exception('This should not get called.');
    }
}
