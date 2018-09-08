<?php
declare(strict_types=1);

namespace Crell\Tukio;


class MockSubscriber implements SubscriberInterface
{
    public function onA(CollectingTask $event) : void
    {
        $event->add('A');
    }
    public function onB(CollectingTask $event) : void
    {
        $event->add('B');
    }
    public function onC(CollectingTask $event) : void
    {
        $event->add('C');
    }
    public function onD(CollectingTask $event) : void
    {
        $event->add('D');
    }
    public function onE(CollectingTask $event) : void
    {
        $event->add('E');
    }

    public function notNormalName(CollectingTask $task) : void
    {
        $task->add('F');
    }

    public function onG(NoEvent $event) : void
    {
        $event->add('G');
    }

    public function ignoredMethodThatDoesNothing() : void
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

