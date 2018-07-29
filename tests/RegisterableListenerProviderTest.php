<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

class EventOne extends CollectingEvent {}

class EventTwo extends CollectingEvent {}

class RegisterableListenerProviderTest extends TestCase
{


    public function test_only_type_correct_listeners_are_returned(): void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener(function (EventOne $event) {
            $event->add('Y');
        });
        $p->addListener(function (CollectingEvent $event) {
            $event->add('Y');
        });
        $p->addListener(function (EventTwo $event) {
            $event->add('N');
        });
        // This class doesn't exist but should not result in an error.
        $p->addListener(function (NoEvent $event) {
            $event->add('F');
        });

        $event = new EventOne();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('YY', implode($event->result()));
    }

    public function test_add_ordered_listeners(): void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener(function (CollectingEvent $event) {
            $event->add('E');
        }, 0);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('C');
        }, 100);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_before(): void
    {
        $p = new RegisterableListenerProvider();

        $p->addListener(function (CollectingEvent $event) {
            $event->add('E');
        }, 0);
        $rid = $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListenerBefore($rid, function (CollectingEvent $event) {
            $event->add('C');
        });
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_after(): void
    {
        $p = new RegisterableListenerProvider();

        $rid = $p->addListener(function (CollectingEvent $event) {
            $event->add('R');
        }, 90);
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListenerBefore($rid, function (CollectingEvent $event) {
            $event->add('C');
        });
        $p->addListener(function (CollectingEvent $event) {
            $event->add('L');
        }, 0);
        $p->addListenerAfter($rid, function (CollectingEvent $event) {
            $event->add('E');
        });

        $event = new CollectingEvent();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_service(): void
    {
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

        $p = new RegisterableListenerProvider($container);

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

    public function test_service_registration_fails_without_container(): void
    {
        $this->expectException(ContainerMissingException::class);

        $p = new RegisterableListenerProvider();

        $p->addListenerService('L', 'hear', CollectingEvent::class, 70);
    }
}
