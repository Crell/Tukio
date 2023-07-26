<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;

function listenerA(CollectingEvent $event): void
{
    $event->add('A');
}

function listenerB(CollectingEvent $event): void
{
    $event->add('B');
}

/**
 * @throws \Exception
 */
function noListen(EventOne $event): void
{
    throw new \Exception('This should not be called');
}

class Listen
{
    public static function listen(CollectingEvent $event): void
    {
        $event->add('C');
    }
}

class ListenService
{
    public static function listen(CollectingEvent $event): void
    {
        $event->add('D');
    }
}

class CompiledEventDispatcherTest extends TestCase
{
    use MakeCompiledProviderTrait;

    function test_compiled_provider_triggers_in_order(): void
    {
        $class = 'CompiledProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new ListenService());

        $builder->addListener('\\Crell\\Tukio\\listenerA');
        $builder->addListener('\\Crell\\Tukio\\listenerB');
        $builder->addListener('\\Crell\\Tukio\\noListen');
        $builder->addListener([Listen::class, 'listen']);
        $builder->addListenerService('D', 'listen', CollectingEvent::class);

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = $event->result();
        $this->assertContains('A', $result);
        $this->assertContains('B', $result);
        $this->assertContains('C', $result);
        $this->assertContains('D', $result);

        $this->assertTrue(true);
    }

    public function test_add_subscriber(): void
    {
        // This test is parallel to and uses the same mock subscriber as
        // OrderedListenerProviderServiceTest::test_add_subscriber().
        // Thus if both work it means the same subscriber works the same
        // transparently in both compiled and non-compiled versions.

        $class = 'SubscriberProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();
        $subscriber = new MockSubscriber();

        $container->addService('subscriber', $subscriber);
        $builder->addSubscriber(MockSubscriber::class, 'subscriber');

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BCAEDF', implode($event->result()));
    }

    public function test_natural_id_on_compiled_provider(): void
    {
        $class = 'NaturalIdProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $builder->addListener("{$ns}event_listener_one", -4);
        $builder->addListenerBefore("{$ns}event_listener_one", "{$ns}event_listener_two");
        $builder->addListenerAfter("{$ns}event_listener_two", "{$ns}event_listener_three");
        $builder->addListenerAfter("{$ns}event_listener_three", "{$ns}event_listener_four");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BACD', implode($event->result()));
    }

    public function test_explicit_id_on_compiled_provider(): void
    {
        $class = 'ExplicitIdProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $builder->addListener("{$ns}event_listener_one", -4, 'id-1');
        $builder->addListenerBefore('id-1', "{$ns}event_listener_two", 'id-2');
        $builder->addListenerAfter('id-2', "{$ns}event_listener_three", 'id-3');
        $builder->addListenerAfter('id-3', "{$ns}event_listener_four");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BACD', implode($event->result()));
    }

    public function test_optimize_event(): void
    {
        $class = 'OptimizedEventProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();
        $container = new MockContainer();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $builder->addListener("{$ns}event_listener_one", -4, 'id-1');
        $builder->addListenerBefore('id-1', "{$ns}event_listener_two", 'id-2');
        $builder->addListenerAfter('id-2', "{$ns}event_listener_three", 'id-3');
        $builder->addListenerAfter('id-3', "{$ns}event_listener_four");

        $builder->optimizeEvent(CollectingEvent::class);

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('BACD', implode($event->result()));
    }
}
