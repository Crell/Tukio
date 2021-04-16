<?php

declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

/**
 * @requires PHP >= 8.0
 */
class OrderedListenerProviderAttributeServiceTest extends TestCase
{
    public function test_add_subscriber() : void
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

        $this->assertEquals('BCAEDF', implode($event->result()));
    }
}
