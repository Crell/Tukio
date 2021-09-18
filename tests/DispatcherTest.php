<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DispatcherTest extends TestCase
{

    /** @var LoggerInterface */
    protected $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new class extends AbstractLogger {
            public $messages = [];

            public function log($level, $message, array $context = []): void
            {
                $this->messages[$level][] = [
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
    }

    public function test_dispatcher_calls_all_listeners() : void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                yield function (CollectingEvent $event) { $event->add('C'); };
                yield function (CollectingEvent $event) { $event->add('R'); };
                yield function (CollectingEvent $event) { $event->add('E'); };
                yield function (CollectingEvent $event) { $event->add('L'); };
                yield function (CollectingEvent $event) { $event->add('L'); };
            }
        };

        $p = new Dispatcher($provider);

        $event = new CollectingEvent();
        $p->dispatch($event);

        $this->assertEquals('CRELL', implode($event->result()));
    }

    public function test_stoppable_events_stop() : void {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                yield function (StoppableCollectingEvent $event) { $event->add('C'); };
                yield function (StoppableCollectingEvent $event) { $event->add('R'); };
                yield function (StoppableCollectingEvent $event) { $event->add('E'); $event->stopPropagation(); };
                yield function (StoppableCollectingEvent $event) { $event->add('L'); };
                yield function (StoppableCollectingEvent $event) { $event->add('L'); };
            }
        };

        $p = new Dispatcher($provider);

        $event = new StoppableCollectingEvent();
        $p->dispatch($event);

        $this->assertEquals('CRE', implode($event->result()));
    }

    public function test_listener_exception_logged() : void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                yield function (CollectingEvent $event) { $event->add('C'); };
                yield function (CollectingEvent $event) { $event->add('R'); };
                yield function (CollectingEvent $event) { throw new \Exception('Fail!'); };
                yield function (CollectingEvent $event) { $event->add('L'); };
                yield function (CollectingEvent $event) { $event->add('L'); };
            }
        };

        $p = new Dispatcher($provider, $this->logger);

        $event = new CollectingEvent();
        try {
            $p->dispatch($event);
            $this->fail('No exception was bubbled up.');
        }
        catch (\Exception $e) {
            $this->assertEquals('Fail!', $e->getMessage());
        }

        $this->assertEquals('CR', implode($event->result()));

        $this->assertArrayHasKey(LogLevel::WARNING, $this->logger->messages);
        $this->assertCount(1, $this->logger->messages[LogLevel::WARNING]);
        $entry = $this->logger->messages[LogLevel::WARNING][0];
        $this->assertEquals('Unhandled exception thrown from listener while processing event.', $entry['message']);
        $this->assertEquals($event, $entry['context']['event']);
    }

    public function test_already_stopped_event_calls_no_listeners() : void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(object $event): iterable
            {
                yield function (CollectingEvent $event) { $event->add('C'); };
            }
        };

        $d = new Dispatcher($provider);

        $event = new StoppableCollectingEvent();
        $event->stopPropagation();

        $d->dispatch($event);

        $this->assertEquals('', implode($event->result()));
    }
}
