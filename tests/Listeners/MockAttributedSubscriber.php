<?php

declare(strict_types=1);

namespace Crell\Tukio\Listeners;


use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Listener;
use Crell\Tukio\ListenerAfter;
use Crell\Tukio\ListenerBefore;
use Crell\Tukio\ListenerPriority;
use Crell\Tukio\NoEvent;

class MockAttributedSubscriber
{
    #[Listener(id: 'a')]
    public function onA(CollectingEvent $event) : void
    {
        $event->add('A');
    }

    #[ListenerPriority(priority: 5)]
    public function onB(CollectingEvent $event) : void
    {
        $event->add('B');
    }

    #[ListenerBefore(before: 'a')]
    public function onC(CollectingEvent $event) : void
    {
        $event->add('C');
    }

    #[ListenerAfter(after: 'a')]
    public function onD(CollectingEvent $event) : void
    {
        $event->add('D');
    }

    public function onE(CollectingEvent $event) : void
    {
        $event->add('E');
    }

    #[ListenerPriority(priority: -5)]
    public function notNormalName(CollectingEvent $event) : void
    {
        $event->add('F');
    }

    #[Listener]
    // @phpstan-ignore-next-line
    public function onG(NoEvent $event) : void
    {
        // @phpstan-ignore-next-line
        $event->add('G');
    }

    public function ignoredMethodThatDoesNothing() : void
    {
        throw new \Exception('What are you doing here?');
    }

    public function ignoredMethodWithOnInTheName_on() : void
    {
        throw new \Exception('What are you doing here?');
    }
}

