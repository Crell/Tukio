<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;

#[ListenerPriority(3, 'A', CollectingEvent::class)]
function atListenerA(CollectingEvent $event): void
{
    $event->add('A');
}

#[ListenerAfter('A')]
function atListenerB(CollectingEvent $event): void
{
    $event->add('B');
}

/**
 * @throws \Exception
 */
#[Listener('nope')]
function atNoListen(EventOne $event): void
{
    throw new \Exception('This should not be called');
}

class AtListen
{
    #[Listener]
    public static function listen(CollectingEvent $event): void
    {
        $event->add('C');
    }
}

class AtListenService
{
    public static function listen(CollectingEvent $event): void
    {
        $event->add('D');
    }
}

class CompiledEventDispatcherAttributeTest extends TestCase
{
    use MakeCompiledProviderTrait;

    function test_compiled_provider_triggers_in_order(): void
    {
        $class = 'AtCompiledProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new AtListenService());

        $ns = "\\Crell\\Tukio";

        $builder->addListener("{$ns}\\atListenerB");
        $builder->addListener("{$ns}\\atListenerA");
        $builder->addListener([AtListen::class, 'listen']);
        $builder->addListener("{$ns}\\atNoListen");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('ABC', implode($event->result()));
    }

    public function test_add_subscriber(): void
    {
        // This test is parallel to and uses the same mock subscriber as
        // RegisterableListenerProviderServiceTest::test_add_subscriber().
        // Thus if both work it means the same subscriber works the same
        // transparently in both compiled and non-compiled versions.

        $class = 'AtSubscriberProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();
        $subscriber = new MockAttributedSubscriber();

        $container->addService('subscriber', $subscriber);
        $builder->addSubscriber(MockSubscriber::class, 'subscriber');

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BCAEDF', implode($event->result()));
    }
}
