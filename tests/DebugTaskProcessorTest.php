<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\TaskInterface;
use Psr\Event\Dispatcher\TaskProcessorInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DebugTaskProcessorTest extends TestCase
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
        $inner = new class implements TaskProcessorInterface {
            public function process(TaskInterface $task): TaskInterface
            {
                return $task;
            }
        };

        $p = new DebugTaskProcessor($inner, $this->logger);

        $task = new CollectingTask();
        $p->process($task);

        $this->assertCount(1, $this->logger->messages[LogLevel::DEBUG]);
        $this->assertEquals('Processing task of type {type}.', $this->logger->messages[LogLevel::DEBUG][0]['message']);
        $this->assertEquals($task, $this->logger->messages[LogLevel::DEBUG][0]['context']['task']);
    }
}
