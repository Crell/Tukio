<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Events\EventOne;
use Crell\Tukio\Fakes\MockContainer;
use Crell\Tukio\Listeners\ArbitraryListener;
use Crell\Tukio\Listeners\CompoundListener;
use Crell\Tukio\Listeners\InvalidListener;
use Crell\Tukio\Listeners\InvokableListener;
use Crell\Tukio\Listeners\MockSubscriber;
use Crell\Tukio\Listeners\TestAttributedListeners;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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

class CompiledListenerProviderTest extends TestCase
{
    use MakeCompiledProviderTrait;

    #[Test]
    public function compiled_provider_triggers_in_order(): void
    {
        $class = 'CompiledProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new Listeners\ListenService());

        $builder->addListener('\\Crell\\Tukio\\listenerA');
        $builder->addListener('\\Crell\\Tukio\\listenerB');
        $builder->addListener('\\Crell\\Tukio\\noListen');
        $builder->addListener([Listeners\Listen::class, 'listen']);
        $builder->addListenerService('D', 'listen', CollectingEvent::class);

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = $event->result();
        self::assertContains('A', $result);
        self::assertContains('B', $result);
        self::assertContains('C', $result);
        self::assertContains('D', $result);

        self::assertTrue(true);
    }

    #[Test]
    public function add_subscriber(): void
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

        // We can't guarantee a stricter order than the instructions provided, so
        // just check for those rather than a precise order.
        $result = implode($event->result());
        self::assertTrue(strpos($result, 'B') < strpos($result, 'A'));
        self::assertTrue(strpos($result, 'C') < strpos($result, 'A'));
        self::assertTrue(strpos($result, 'D') > strpos($result, 'A'));
        self::assertTrue(strpos($result, 'F') > strpos($result, 'A'));
    }

    #[Test]
    public function natural_id_on_compiled_provider(): void
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

        self::assertEquals('BACD', implode($event->result()));
    }

    #[Test]
    public function explicit_id_on_compiled_provider(): void
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

        self::assertEquals('BACD', implode($event->result()));
    }

    #[Test]
    public function optimize_event(): void
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

        $builder->optimizeEvents(CollectingEvent::class);

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('BACD', implode($event->result()));
    }

    #[Test]
    public function anonymous_class_compile(): void
    {
        $builder = new ProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new Listeners\ListenService());

        $builder->addListener('\\Crell\\Tukio\\listenerA');
        $builder->addListener('\\Crell\\Tukio\\listenerB');
        $builder->addListener('\\Crell\\Tukio\\noListen');
        $builder->addListener([Listeners\Listen::class, 'listen']);
        $builder->addListenerService('D', 'listen', CollectingEvent::class);

        $provider = $this->makeAnonymousProvider($builder, $container);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = $event->result();
        self::assertContains('A', $result);
        self::assertContains('B', $result);
        self::assertContains('C', $result);
        self::assertContains('D', $result);

        self::assertTrue(true);
    }

    #[Test]
    public function optimize_event_anonymous_class(): void
    {
        $builder = new ProviderBuilder();
        $container = new MockContainer();

        // Just to make the following lines shorter and easier to read.
        $ns = '\\Crell\\Tukio\\';

        $builder->addListener("{$ns}event_listener_one", -4, 'id-1');
        $builder->addListenerBefore('id-1', "{$ns}event_listener_two", 'id-2');
        $builder->addListenerAfter('id-2', "{$ns}event_listener_three", 'id-3');
        $builder->addListenerAfter('id-3', "{$ns}event_listener_four");

        $builder->optimizeEvents(CollectingEvent::class);

        $provider = $this->makeAnonymousProvider($builder, $container);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('BACD', implode($event->result()));
    }

    #[Test, DataProvider('detection_class_examples')]
    public function detects_invoke_method_and_type(string $class): void
    {
        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $container->addService($class, new $class());

        $builder->listenerService($class);

        $provider = $this->makeAnonymousProvider($builder, $container);

        $event = new CollectingEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals($class, $event->result()[0]);
    }

    public static function detection_class_examples(): iterable
    {
        return [
            [InvokableListener::class],
            [ArbitraryListener::class],
            [CompoundListener::class],
        ];
    }

    #[Test]
    public function rejects_multi_method_class_without_invoke(): void
    {
        $this->expectException(ServiceRegistrationTooManyMethods::class);
        $container = new MockContainer();

        $container->addService(InvalidListener::class, new InvalidListener());

        $builder = new ProviderBuilder();

        $builder->listenerService(InvalidListener::class);
    }

    #[Test]
    public function rejects_missing_auto_detected_service(): void
    {
        $this->expectException(ServiceRegistrationClassNotExists::class);
        $container = new MockContainer();

        $provider = new OrderedListenerProvider($container);

        $builder = new ProviderBuilder();

        // @phpstan-ignore-next-line
        $builder->listenerService(DoesNotExist::class);
    }

    #[Test]
    public function add_attribute_based_service_methods(): void
    {
        $builder = new ProviderBuilder();
        $container = new MockContainer();

        $container->addService(TestAttributedListeners::class, new TestAttributedListeners());

        $builder->listenerService(TestAttributedListeners::class, 'listenerC');
        $builder->listenerService(TestAttributedListeners::class, 'listenerD');

        $provider = $this->makeAnonymousProvider($builder, $container);

        $event = new CollectingEvent();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertEquals('DC', implode($event->result()));
    }
}
