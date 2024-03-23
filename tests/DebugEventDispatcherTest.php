<?php

declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\Events\CollectingEvent;
use Crell\Tukio\Fakes\MockLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;

class DebugEventDispatcherTest extends TestCase
{
    protected MockLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new MockLogger();
    }

    #[Test]
    public function event_is_logged() : void
    {
        $inner = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $p = new DebugEventDispatcher($inner, $this->logger);

        $event = new CollectingEvent();
        $p->dispatch($event);

        self::assertCount(1, $this->logger->messages[LogLevel::DEBUG]);
        self::assertEquals('Processing event of type {type}.', $this->logger->messages[LogLevel::DEBUG][0]['message']);
        self::assertEquals($event, $this->logger->messages[LogLevel::DEBUG][0]['context']['event']);
    }
}
