<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class NotifierTest extends TestCase
{
    protected $counter;

    /** @var ListenerProviderInterface */
    protected $provider;

    /** @var LoggerInterface */
    protected $logger;

    public function setUp()
    {
        parent::setUp();

        // This counter object lets us track which listeners were called without violating the
        // immutability of the event object.
        $this->counter = new class {
            protected $counts = [];
            public function inc(string $key) {
                $this->counts[$key] = isset($this->counts[$key]) ? $this->counts[$key] + 1 : 1;
            }

            public function countOf(string $key) {
                return isset($this->counts[$key]) ? $this->counts[$key] : 0;
            }
        };

        $this->provider = new class implements ListenerProviderInterface {
            /** @var array */
            protected $listeners;

            public function addListener($listener) : void
            {
                $this->listeners[] = $listener;
            }

            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield from $this->listeners;
            }
        };

        $this->logger = new class extends AbstractLogger {
                public $messages = [];

            public function log($level, $message, array $context = [])
            {
                $this->messages[$level][] = [
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
    }

    public function test_notifier_calls_all_listeners() : void
    {
        $counter = $this->counter;

        $this->provider->addListener(function (BasicMessage $e) use ($counter) {
            $counter->inc('A');
        });

        $this->provider->addListener(function (BasicMessage $e) use ($counter) {
            $counter->inc('B');
        });

        $d = new Notifier($this->provider);

        $d->notify(new BasicMessage());

        $this->assertEquals(1, $this->counter->countOf('A'));
        $this->assertEquals(1, $this->counter->countOf('B'));
    }

    public function test_listener_exception_logs_not_dies() : void
    {
        $counter = $this->counter;

        $this->provider->addListener(function (BasicMessage $e) use ($counter) {
            $counter->inc('A');
        });

        $this->provider->addListener(function (BasicMessage $e) use ($counter) {
            throw new \Exception('Stop the world,  want to get off.');
        });

        $this->provider->addListener(function (BasicMessage $e) use ($counter) {
            $counter->inc('C');
        });

        $logger = $this->logger;

        $d = new Notifier($this->provider, $logger);

        $basicMessage = new BasicMessage();
        $d->notify(new BasicMessage());

        $this->assertEquals(1, $this->counter->countOf('A'));
        $this->assertEquals(1, $this->counter->countOf('C'));

        $this->assertArrayHasKey(LogLevel::WARNING, $logger->messages);
        $this->assertCount(1, $logger->messages[LogLevel::WARNING]);
        $entry = $logger->messages[LogLevel::WARNING][0];
        $this->assertEquals('Unhandled exception thrown from listener while processing message.', $entry['message']);
        $this->assertEquals($basicMessage, $entry['context']['message']);
    }
}
