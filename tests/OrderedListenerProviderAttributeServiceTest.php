<?php

declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderedListenerProviderAttributeServiceTest extends TestCase
{
    #[Test]
    public function add_subscriber() : void
    {
        $container = new MockContainer();

        $subscriber = new MockAttributedSubscriber();

        $container->addService('subscriber', $subscriber);

        $p = new OrderedListenerProvider($container);

        $p->addSubscriber(MockAttributedSubscriber::class, 'subscriber');

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        // We can't guarantee a stricter order than the instructions provided, so
        // just check for those rather than a precise order.
        $result = implode($event->result());
        self::assertTrue(strpos($result, 'B') < strpos($result, 'A'));
        self::assertTrue(strpos($result, 'C') < strpos($result, 'A'));
        self::assertTrue(strpos($result, 'D') > strpos($result, 'A'));
        self::assertTrue(strpos($result, 'F') > strpos($result, 'A'));
    }
}
