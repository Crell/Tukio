<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\ListenerPriority;

#[ListenerPriority(priority: 5)]
class InvokableListenerClassNoId
{
    public function __invoke(CollectingEvent $event): void
    {
        $event->add(static::class);
    }
}
