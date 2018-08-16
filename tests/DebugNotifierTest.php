<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\MessageInterface;
use Psr\Event\Dispatcher\MessageNotifierInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DebugNotifierTest extends TestCase
{

    /** @var LoggerInterface */
    protected $logger;

    public function setUp()
    {
        parent::setUp();

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

    public function test_event_is_logged() : void
    {
        $inner = new class implements MessageNotifierInterface {
            public function notify(MessageInterface $message): void {}
        };

        $p = new DebugNotifier($inner, $this->logger);

        $message = new BasicMessage();
        $p->notify($message);

        $this->assertCount(1, $this->logger->messages[LogLevel::DEBUG]);
        $this->assertEquals('Notifying message of type {type}.', $this->logger->messages[LogLevel::DEBUG][0]['message']);
        $this->assertEquals($message, $this->logger->messages[LogLevel::DEBUG][0]['context']['message']);
    }
}
