<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

class RegisterableListenerProviderServiceTest extends TestCase
{
    /** @var MockContainer */
    protected $mockContainer;

    public function setUp()
    {
        parent::setUp();

        $container = new MockContainer();

        $container->addService('A', new class
        {
            public function listen(CollectingEvent $event)
            {
                $event->add('A');
            }
        });
        $container->addService('B', new class
        {
            public function listen(CollectingEvent $event)
            {
                $event->add('B');
            }
        });
        $container->addService('C', new class
        {
            public function listen(CollectingEvent $event)
            {
                $event->add('C');
            }
        });
        $container->addService('R', new class
        {
            public function listen(CollectingEvent $event)
            {
                $event->add('R');
            }
        });
        $container->addService('E', new class
        {
            public function listen(CollectingEvent $event)
            {
                $event->add('E');
            }
        });
        $container->addService('L', new class
        {
            public function hear(CollectingEvent $event)
            {
                $event->add('L');
            }
        });

        $this->mockContainer = $container;
    }

    public function test_add_listener_service(): void
    {
        $p = new RegisterableListenerProvider($this->mockContainer);

        $p->addListenerService('L', 'hear', CollectingEvent::class, 70);
        $p->addListenerService('E', 'listen', CollectingEvent::class, 80);
        $p->addListenerService('C', 'listen', CollectingEvent::class, 100);
        $p->addListenerService('L', 'hear', CollectingEvent::class); // Defaults to 0
        $p->addListenerService('R', 'listen', CollectingEvent::class, 90);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_service_before_another(): void
    {
        $p = new RegisterableListenerProvider($this->mockContainer);

        $l1 = $p->addListenerService('L', 'hear', CollectingEvent::class);
        $l2 = $p->addListenerServiceBefore($l1, 'L', 'hear', CollectingEvent::class);
        $e = $p->addListenerServiceBefore($l2, 'E', 'listen', CollectingEvent::class);
        $r = $p->addListenerServiceBefore($e, 'R', 'listen', CollectingEvent::class);
        $c = $p->addListenerServiceBefore($r, 'C', 'listen', CollectingEvent::class);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_service_after_another(): void
    {
        $p = new RegisterableListenerProvider($this->mockContainer);

        $c = $p->addListenerService('C', 'listen', CollectingEvent::class);
        $r = $p->addListenerServiceAfter($c, 'R', 'listen', CollectingEvent::class);
        $e = $p->addListenerServiceAfter($r, 'E', 'listen', CollectingEvent::class);
        $l1 = $p->addListenerServiceAfter($e, 'L', 'hear', CollectingEvent::class);
        $l2 = $p->addListenerServiceAfter($l1, 'L', 'hear', CollectingEvent::class);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_service_registration_fails_without_container(): void
    {
        $this->expectException(ContainerMissingException::class);

        $p = new RegisterableListenerProvider();

        $p->addListenerService('L', 'hear', CollectingEvent::class, 70);
    }


    public function test_add_unordered_subscriber() : void {
        $container = new MockContainer();

        $subscriber = new MockSubscriber();

        $container->addService('subscriber', $subscriber);

        $p = new RegisterableListenerProvider($container);

        $p->addSubscriber(MockSubscriber::class, 'subscriber');

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('ABCDE', implode($event->result()));
    }

}

class MockSubscriber
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

    public function onF(NoEvent $event) : void
    {
        $event->add('F');
    }

    /*
    public static function getSubscribers(): iterable
    {
        return [
            ['method' => 'a', 'type' => CollectingEvent::class, 'priority' => 10],  // Specify everything.
            ['method' => 'b', 'priority' => 9], // Both type and prioirty can be omitted.
            'd',  // Just list the method, the rest is default/autodetected. The most common case.
            ['method' => 'c', 'type' => CollectingEvent::class], // Both type and prioirty can be omitted.
            ['e' => -5], // You can short-case the method/priority, but not the type. Use the full version for that.
            'f', // This one shouldn't fire.
        ];
    }
    */
}
