<?php

declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DispatcherTest extends TestCase
{
    protected MockLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new MockLogger();
    }

    #[Test]
    public function dispatcher_calls_all_listeners() : void
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

        self::assertEquals('CRELL', implode($event->result()));
    }

    #[Test]
    public function stoppable_events_stop() : void {
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

        self::assertEquals('CRE', implode($event->result()));
    }

    #[Test]
    public function listener_exception_logged() : void
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
            self::assertEquals('Fail!', $e->getMessage());
        }

        self::assertEquals('CR', implode($event->result()));

        self::assertArrayHasKey(LogLevel::WARNING, $this->logger->messages);
        self::assertCount(1, $this->logger->messages[LogLevel::WARNING]);
        $entry = $this->logger->messages[LogLevel::WARNING][0];
        self::assertEquals('Unhandled exception thrown from listener while processing event.', $entry['message']);
        self::assertEquals($event, $entry['context']['event']);
    }

    #[Test]
    public function already_stopped_event_calls_no_listeners() : void
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

        self::assertEquals('', implode($event->result()));
    }
}
