<?php

declare(strict_types=1);

namespace Crell\Tukio\Listeners;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\ListenerProxy;

class MockMalformedSubscriber
{
    /**
     * This function should succeed in automatic registration.
     */
    public function onA(CollectingEvent $event): void
    {
        $event->add('A');
    }

    /**
     * This function should have automatic registration attempted, and fail due to missing a type.
     */
    // @phpstan-ignore-next-line
    public function onNone($event): void
    {
        $event->add('A');
    }

    /**
     * This function should have manual registration attempted, and fail due to missing a type.
     */
    // @phpstan-ignore-next-line
    public function abnormalNameWithoutType($event): void
    {
        $event->add('B');
    }

    public static function registerListenersDirect(ListenerProxy $proxy): void
    {
        $a = $proxy->addListener('onA');
        // Should fail and throw an exception:
        $proxy->addListener('abnormalNameWithoutType');
    }

    public static function registerListenersBefore(ListenerProxy $proxy): void
    {
        $a = $proxy->addListener('onA');
        // Should fail and throw an exception:
        $proxy->addListenerBefore($a, 'abnormalNameWithoutType');
    }

    public static function registerListenersAfter(ListenerProxy $proxy): void
    {
        $a = $proxy->addListener('onA');
        // Should fail and throw an exception:
        $proxy->addListenerAfter($a, 'abnormalNameWithoutType');
    }
}
