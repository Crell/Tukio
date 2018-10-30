<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

function notifyListenerA(MessageOne $event) : void
{
    CompiledMessageEventDispatcherTest::$results[] = 'A';
}

function notifyListenerB(MessageOne $event) : void
{
    CompiledMessageEventDispatcherTest::$results[] = 'B';
}

/**
 * @throws \Exception
 */
function notifierNoListen(MessageTwo $event) : void
{
    throw new \Exception('This should not be called');
}

class NotificationListen
{
    public static function listen(MessageOne $event)
    {
        CompiledMessageEventDispatcherTest::$results[] = 'C';
    }
}

class NotificationListenService
{

    public static function listen(MessageOne $event)
    {
        CompiledMessageEventDispatcherTest::$results[] = 'D';
    }
}

class CompiledMessageEventDispatcherTest extends TestCase
{
    use MakeCompiledProviderTrait;

    public static $results = [];

    public function setUp()
    {
        parent::setUp();
        static::$results = [];
    }

    function test_compiled_provider_triggers()
    {
        $class = 'CompiledProvider';
        $namespace = 'Test\\Notifier';

        $builder = new NotificationProviderBuilder();

        $container = new MockContainer();
        $container->addService('D', new NotificationListenService());

        $builder->addListener('\\Crell\\Tukio\\notifyListenerA');
        $builder->addListener('\\Crell\\Tukio\\notifyListenerB');
        $builder->addListener('\\Crell\\Tukio\\notifierNoListen');
        $builder->addListener([NotificationListen::class, 'listen']);
        $builder->addListenerService('D', 'listen', MessageOne::class);

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new MessageOne();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $expected = ['A', 'B', 'C', 'D'];
        $res = static::$results;
        sort($res);
        $this->assertEquals($expected, $res);
    }

    public function test_add_subscriber()
    {
        // This test is parallel to and uses the same mock subscriber as
        // RegisterableListenerProviderServiceTest::test_add_subscriber().
        // Thus if both work it means the same subscriber works the same
        // transparently in both compiled and non-compiled versions.

        $class = 'SubscriberProvider';
        $namespace = 'Test\\Notification';

        $builder = new NotificationProviderBuilder();
        $container = new MockContainer();
        $subscriber = new MockMessageSubscriber();

        $container->addService('subscriber', $subscriber);
        $builder->addSubscriber(MockMessageSubscriber::class, 'subscriber');

        $provider = $this->makeProvider($builder, $container, $class, $namespace);

        $event = new MessageOne();
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $expected = ['A', 'B', 'C', 'D', 'E', 'F'];
        $res = $subscriber->results;
        sort($res);
        $this->assertEquals($expected, $res);
    }

}
