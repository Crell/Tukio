<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\ListenerBefore;
use Crell\Tukio\ListenerPriority;

#[ListenerBefore(InvokableListenerClassNoId::class)]
class InvokableListenerClassNoIdBefore
{
    public function __invoke(CollectingEvent $event): void
    {
        $event->add(static::class);
    }
}
