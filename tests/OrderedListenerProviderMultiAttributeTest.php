<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[ListenerAfter('b')]
#[ListenerAfter('listener_c')]
function listener_a(CollectingEvent $event): void
{
    $event->add('A');
}

#[Listener('b')]
function listener_b(CollectingEvent $event): void
{
    $event->add('B');
}

function listener_c(CollectingEvent $event): void
{
    $event->add('C');
}

#[ListenerBefore('listener_a')]
#[ListenerBefore('b')]
function listener_d(CollectingEvent $event): void
{
    $event->add('D');
}

class OrderedListenerProviderMultiAttributeTest extends TestCase
{
    #[Test]
    public function ordering_with_multiple_before_after_rules_works(): void
    {
        $p = new OrderedListenerProvider();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $p->listener("{$ns}listener_a");
        $p->listener("{$ns}listener_b");
        $p->listener("{$ns}listener_c");
        $p->listener("{$ns}listener_d");

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = implode($event->result());
        self::assertTrue(strpos($result, 'A') > strpos($result, 'B'));
        self::assertTrue(strpos($result, 'A') > strpos($result, 'C'));
        self::assertTrue(strpos($result, 'D') < strpos($result, 'A'));
        self::assertTrue(strpos($result, 'D') < strpos($result, 'B'));
    }

}
