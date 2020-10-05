<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

/**
 * @requires PHP >= 8.0
 */
class OrderedListenerProviderAttributeServiceTest extends TestCase
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
            #[ListenerBefore(id: 'c', before: 'r')]
            public function listen(CollectingEvent $event)
            {
                $event->add('C');
            }
        });
        $container->addService('R', new class
        {
            #[Listener(id: 'r', priority: 1)]
            public function listen(CollectingEvent $event)
            {
                $event->add('R');
            }
        });
        $container->addService('E', new class
        {
            #[Listener(id: 'e')]
            public function listen(CollectingEvent $event)
            {
                $event->add('E');
            }
        });
        $container->addService('L', new class
        {
            #[ListenerAfter('e')]
            public function hear(CollectingEvent $event)
            {
                $event->add('L');
            }
        });

        $this->mockContainer = $container;
    }

    public function test_add_listener_service(): void
    {
        $p = new OrderedListenerProvider($this->mockContainer);

        $p->addListenerService('E', 'listen', CollectingEvent::class);
        $p->addListenerService('C', 'listen', CollectingEvent::class);
        $p->addListenerService('L', 'hear', CollectingEvent::class);
        $p->addListenerService('R', 'listen', CollectingEvent::class);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CREL', implode($event->result()));
    }

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
