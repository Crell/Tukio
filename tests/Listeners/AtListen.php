<?php

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Listener;

class AtListen
{
    #[Listener]
    public static function listen(CollectingEvent $event): void
    {
        $event->add('C');
    }
}
