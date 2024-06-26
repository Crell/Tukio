<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Events\EventOne;
use Crell\Tukio\Fakes\MockContainer;
use Crell\Tukio\Listeners\MockAttributedSubscriber;
use Crell\Tukio\Listeners\MockSubscriber;
use PHPUnit\Framework\Attributes\Test;
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

class CompiledListenerProviderAttributeTest extends TestCase
{
    use MakeCompiledProviderTrait;

    #[Test]
    public function compiled_provider_triggers_in_order(): void
    {
        $class = 'AtCompiledProvider';
        $namespace = 'Test\\Space';

        $builder = new ProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new Listeners\AtListenService());

        $ns = "\\Crell\\Tukio";

        $builder->addListener("{$ns}\\atListenerB");
        $builder->addListener("{$ns}\\atListenerA");
        $builder->addListener([Listeners\AtListen::class, 'listen']);
        $builder->addListener("{$ns}\\atNoListen");

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new CollectingEvent();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $result = implode($event->result());
        self::assertTrue(strpos($result, 'B') > strpos($result, 'A'));
    }

    #[Test]
    public function add_subscriber(): void
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
        $builder->addSubscriber(MockAttributedSubscriber::class, 'subscriber');

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
}
