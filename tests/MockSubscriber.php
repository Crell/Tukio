<?php

declare(strict_types=1);

namespace Crell\Tukio;


class MockSubscriber implements SubscriberInterface
{
    public function onA(CollectingEvent $event) : void
    {
        $event->add('A');
    }
    public function onB(CollectingEvent $event) : void
    {
        $event->add('B');
    }
    public function onC(CollectingEvent $event) : void
    {
        $event->add('C');
    }
    public function onD(CollectingEvent $event) : void
    {
        $event->add('D');
    }
    public function onE(CollectingEvent $event) : void
    {
        $event->add('E');
    }

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

    public static function registerListeners(ListenerProxy $proxy): void
    {
        $a = $proxy->addListener('onA');
        $b = $proxy->addListener('onB', 5);
        $c = $proxy->addListenerBefore($a, 'onC');
        $d = $proxy->addListenerAfter($a, 'onD');
        // Don't register E.  It should self-register by reflection.
        $f = $proxy->addListener('notNormalName', -5);
        $g = $proxy->addListener('onG');
    }
}

