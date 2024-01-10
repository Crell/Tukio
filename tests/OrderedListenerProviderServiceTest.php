<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Fakes\MockContainer;
use Crell\Tukio\Listeners\MockMalformedSubscriber;
use Crell\Tukio\Listeners\MockSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderedListenerProviderServiceTest extends TestCase
{
    protected MockContainer $mockContainer;

    public function setUp(): void
    {
        parent::setUp();

        $container = new MockContainer();

        $container->addService('A', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('A');
            }
        });
        $container->addService('B', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('B');
            }
        });
        $container->addService('C', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('C');
            }
        });
        $container->addService('R', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('R');
            }
        });
        $container->addService('E', new class
        {
            public function listen(CollectingEvent $event): void
            {
                $event->add('E');
            }
        });
        $container->addService('L', new class
        {
            public function hear(CollectingEvent $event): void
            {
                $event->add('L');
            }
        });

        $this->mockContainer = $container;
    }

    #[Test]
    public function add_listener_service(): void
    {
        $p = new OrderedListenerProvider($this->mockContainer);

        $p->addListenerService('L', 'hear', CollectingEvent::class, 70);
        $p->addListenerService('E', 'listen', CollectingEvent::class, 80);
        $p->addListenerService('C', 'listen', CollectingEvent::class, 100);
        $p->addListenerService('L', 'hear', CollectingEvent::class, priority: 0);
        $p->addListenerService('R', 'listen', CollectingEvent::class, 90);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function add_listener_service_before_another(): void
    {
        $p = new OrderedListenerProvider($this->mockContainer);

        $l1 = $p->addListenerService('L', 'hear', CollectingEvent::class);
        $l2 = $p->addListenerServiceBefore($l1, 'L', 'hear', CollectingEvent::class);
        $e = $p->addListenerServiceBefore($l2, 'E', 'listen', CollectingEvent::class);
        $r = $p->addListenerServiceBefore($e, 'R', 'listen', CollectingEvent::class);
        $c = $p->addListenerServiceBefore($r, 'C', 'listen', CollectingEvent::class);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function add_listener_service_after_another(): void
    {
        $p = new OrderedListenerProvider($this->mockContainer);

        $c = $p->addListenerService('C', 'listen', CollectingEvent::class);
        $r = $p->addListenerServiceAfter($c, 'R', 'listen', CollectingEvent::class);
        $e = $p->addListenerServiceAfter($r, 'E', 'listen', CollectingEvent::class);
        $l1 = $p->addListenerServiceAfter($e, 'L', 'hear', CollectingEvent::class);
        $l2 = $p->addListenerServiceAfter($l1, 'L', 'hear', CollectingEvent::class);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function service_registration_fails_without_container(): void
    {
        $this->expectException(ContainerMissingException::class);

        $p = new OrderedListenerProvider();

        $p->addListenerService('L', 'hear', CollectingEvent::class, 70);
    }


    #[Test]
    public function add_subscriber() : void
    {
        $container = new MockContainer();

        $subscriber = new MockSubscriber();

        $container->addService('subscriber', $subscriber);

        $p = new OrderedListenerProvider($container);

        $p->addSubscriber(MockSubscriber::class, 'subscriber');

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

    #[Test]
    public function malformed_subscriber_automatic_fails(): void
    {
        $this->expectException(InvalidTypeException::class);

        $container = new MockContainer();

        $subscriber = new MockMalformedSubscriber();

        $container->addService(MockMalformedSubscriber::class, $subscriber);

        $p = new OrderedListenerProvider($container);

        $p->addSubscriber(MockMalformedSubscriber::class);
    }

    #[Test]
    public function malformed_subscriber_manual_fails(): void
    {
        $this->expectException(InvalidTypeException::class);

        $container = new MockContainer();

        $subscriber = new MockMalformedSubscriber();

        $container->addService('subscriber', $subscriber);

        $provider = new OrderedListenerProvider($container);

        $proxy = new ListenerProxy($provider, 'subscriber', MockMalformedSubscriber::class);

        MockMalformedSubscriber::registerListenersDirect($proxy);
    }

    #[Test]
    public function malformed_subscriber_manual_before_fails(): void
    {
        $this->expectException(InvalidTypeException::class);

        $container = new MockContainer();

        $subscriber = new MockMalformedSubscriber();

        $container->addService('subscriber', $subscriber);

        $provider = new OrderedListenerProvider($container);

        $proxy = new ListenerProxy($provider, 'subscriber', MockMalformedSubscriber::class);

        MockMalformedSubscriber::registerListenersBefore($proxy);
    }

    #[Test]
    public function malformed_subscriber_manual_after_fails(): void
    {
        $this->expectException(InvalidTypeException::class);

        $container = new MockContainer();

        $subscriber = new MockMalformedSubscriber();

        $container->addService('subscriber', $subscriber);

        $provider = new OrderedListenerProvider($container);

        $proxy = new ListenerProxy($provider, 'subscriber', MockMalformedSubscriber::class);

        MockMalformedSubscriber::registerListenersAfter($proxy);
    }

    #[Test, DataProvider('detection_class_examples')]
    public function detects_invoke_method_and_type(string $class): void
    {
        $container = new MockContainer();

        $container->addService($class, new $class());

        $provider = new OrderedListenerProvider($container);

        $provider->listenerService($class);

        $event = new CollectingEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals($class, $event->result()[0]);
    }

    public static function detection_class_examples(): iterable
    {
        return [
            [Listeners\InvokableListener::class],
            [Listeners\ArbitraryListener::class],
            [Listeners\CompoundListener::class],
        ];
    }

    #[Test]
    public function rejects_multi_method_class_without_invoke(): void
    {
        $this->expectException(ServiceRegistrationTooManyMethods::class);
        $container = new MockContainer();

        $container->addService(Listeners\InvalidListener::class, new Listeners\InvalidListener());

        $provider = new OrderedListenerProvider($container);

        $provider->listenerService(Listeners\InvalidListener::class);
    }

    #[Test]
    public function rejects_missing_auto_detected_service(): void
    {
        $this->expectException(ServiceRegistrationClassNotExists::class);
        $container = new MockContainer();

        $provider = new OrderedListenerProvider($container);

        // @phpstan-ignore-next-line
        $provider->listenerService(DoesNotExist::class);
    }

}
