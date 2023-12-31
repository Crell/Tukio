<?php

declare(strict_types=1);

namespace Crell\Tukio;


class MockAttributeSubscriber
{
    public function onA(CollectingEvent $event) : void
    {
        $event->add('A');
    }

    #[ListenerPriority(5)]
    public function onB(CollectingEvent $event) : void
    {
        $event->add('B');
    }

    #[ListenerBefore(__CLASS__ . '-' . 'onA')]
    public function onC(CollectingEvent $event) : void
    {
        $event->add('C');
    }

    #[ListenerAfter(__CLASS__ . '-' . 'onA')]
    public function onD(CollectingEvent $event) : void
    {
        $event->add('D');
    }

    public function onE(CollectingEvent $event) : void
    {
        $event->add('E');
    }

    #[ListenerPriority(-5)]
    public function notNormalName(CollectingEvent $event) : void
    {
        $event->add('F');
    }

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

