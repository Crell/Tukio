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
            public function listen(CollectingTask $event)
            {
                $event->add('A');
            }
        });
        $container->addService('B', new class
        {
            public function listen(CollectingTask $event)
            {
                $event->add('B');
            }
        });
        $container->addService('C', new class
        {
            public function listen(CollectingTask $event)
            {
                $event->add('C');
            }
        });
        $container->addService('R', new class
        {
            public function listen(CollectingTask $event)
            {
                $event->add('R');
            }
        });
        $container->addService('E', new class
        {
            public function listen(CollectingTask $event)
            {
                $event->add('E');
            }
        });
        $container->addService('L', new class
        {
            public function hear(CollectingTask $event)
            {
                $event->add('L');
            }
        });

        $this->mockContainer = $container;
    }

    public function test_add_listener_service(): void
    {
        $p = new RegisterableListenerProvider($this->mockContainer);

        $p->addListenerService('L', 'hear', CollectingTask::class, 70);
        $p->addListenerService('E', 'listen', CollectingTask::class, 80);
        $p->addListenerService('C', 'listen', CollectingTask::class, 100);
        $p->addListenerService('L', 'hear', CollectingTask::class); // Defaults to 0
        $p->addListenerService('R', 'listen', CollectingTask::class, 90);

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_service_before_another(): void
    {
        $p = new RegisterableListenerProvider($this->mockContainer);

        $l1 = $p->addListenerService('L', 'hear', CollectingTask::class);
        $l2 = $p->addListenerServiceBefore($l1, 'L', 'hear', CollectingTask::class);
        $e = $p->addListenerServiceBefore($l2, 'E', 'listen', CollectingTask::class);
        $r = $p->addListenerServiceBefore($e, 'R', 'listen', CollectingTask::class);
        $c = $p->addListenerServiceBefore($r, 'C', 'listen', CollectingTask::class);

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_add_listener_service_after_another(): void
    {
        $p = new RegisterableListenerProvider($this->mockContainer);

        $c = $p->addListenerService('C', 'listen', CollectingTask::class);
        $r = $p->addListenerServiceAfter($c, 'R', 'listen', CollectingTask::class);
        $e = $p->addListenerServiceAfter($r, 'E', 'listen', CollectingTask::class);
        $l1 = $p->addListenerServiceAfter($e, 'L', 'hear', CollectingTask::class);
        $l2 = $p->addListenerServiceAfter($l1, 'L', 'hear', CollectingTask::class);

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_service_registration_fails_without_container(): void
    {
        $this->expectException(ContainerMissingException::class);

        $p = new RegisterableListenerProvider();

        $p->addListenerService('L', 'hear', CollectingTask::class, 70);
    }


    public function test_add_unordered_subscriber() : void {
        $container = new MockContainer();

        $subscriber = new MockSubscriber();

        $container->addService('subscriber', $subscriber);

        $p = new RegisterableListenerProvider($container);

        $p->addSubscriber(MockSubscriber::class, 'subscriber');

        $event = new CollectingTask();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('ABCDE', implode($event->result()));
    }

}
