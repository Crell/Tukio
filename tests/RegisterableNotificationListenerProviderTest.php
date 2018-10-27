<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\MessageInterface;

// These need to extend from MessageInterface, but that means changing a lot more of the test.
class MessageOne implements MessageInterface {}

class MessageTwo implements MessageInterface {}

class RegisterableNotificationListenerProviderTest extends TestCase
{

    /** @var MockContainer */
    protected $mockContainer;

    /** @var array */
    protected $results;

    public function setUp()
    {
        parent::setUp();

        $container = new MockContainer();

        $results = [];
        $this->results = &$results;

        foreach (['A', 'B', 'C', 'R', 'E'] as $name) {
            $container->addService($name, new class($results, $name)
            {
                protected $results;
                protected $name;
                public function __construct(&$results, $name)
                {
                    $this->results = &$results;
                    $this->name = $name;
                }

                public function listen(MessageInterface $event)
                {
                    $this->results[] = $this->name;
                }
            });
        }

        $container->addService('L', new class($results)
        {
            protected $results;
            public function __construct(&$results)
            {
                $this->results = &$results;
            }

            public function hear(MessageInterface $event)
            {
                $this->results[] = 'L';
            }
        });

        $this->mockContainer = $container;
    }

    public function test_only_type_correct_listeners_are_returned(): void
    {
        $p = new RegisterableNotificationListenerProvider();

        $results = [];

        $p->addListener(function (MessageOne $event) use (&$results) {
            $results[] = 'Y';
        });
        $p->addListener(function (MessageInterface $event) use (&$results) {
            $results[] = 'Y';
        });
        $p->addListener(function (MessageTwo $event) use (&$results) {
            $results[] = 'N';
        });
        // This class doesn't exist but should not result in an error.
        $p->addListener(function (NoEvent $event) use (&$results) {
            $results[] = 'F';
        });

        $event = new MessageOne();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $this->assertEquals('YY', implode($results));
    }


    public function test_add_listener_service(): void
    {
        $p = new RegisterableNotificationListenerProvider($this->mockContainer);

        $p->addListenerService('L', 'hear', MessageInterface::class);
        $p->addListenerService('E', 'listen', MessageInterface::class);
        $p->addListenerService('C', 'listen', MessageInterface::class);
        $p->addListenerService('L', 'hear', MessageInterface::class);
        $p->addListenerService('R', 'listen', MessageInterface::class);

        $event = new MessageOne();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $expected = ['C', 'R', 'E', 'L', 'L'];
        sort($expected);
        $res = $this->results;
        sort($res);
        $this->assertEquals($expected, $res);
    }

    public function test_service_registration_fails_without_container(): void
    {
        $this->expectException(ContainerMissingException::class);

        $p = new RegisterableNotificationListenerProvider();

        $p->addListenerService('L', 'hear', MessageInterface::class);
    }

    public function test_add_subscriber() : void
    {
        $container = new MockContainer();

        $subscriber = new MockMessageSubscriber();

        $container->addService('subscriber', $subscriber);

        $p = new RegisterableNotificationListenerProvider($container);

        $p->addSubscriber(MockMessageSubscriber::class, 'subscriber');

        $event = new MessageOne();

        foreach ($p->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        $expected = ['A', 'B', 'C', 'D', 'E', 'F'];
        $res = $subscriber->results;
        sort($res);
        $this->assertEquals($expected, $res);
    }

}
