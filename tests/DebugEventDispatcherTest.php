<?php

declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DebugEventDispatcherTest extends TestCase
{
    protected MockLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new MockLogger();
    }

    public function test_event_is_logged() : void
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

        $this->assertCount(1, $this->logger->messages[LogLevel::DEBUG]);
        $this->assertEquals('Processing event of type {type}.', $this->logger->messages[LogLevel::DEBUG][0]['message']);
        $this->assertEquals($event, $this->logger->messages[LogLevel::DEBUG][0]['context']['event']);
    }
}
