<?php
declare(strict_types=1);

namespace Crell\Tukio;

use PHPUnit\Framework\TestCase;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Event\Dispatcher\MessageInterface;
use Psr\Event\Dispatcher\MessageNotifierInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ProcessorTest extends TestCase
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

    public function test_processor_calls_all_listeners() : void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (CollectingTask $event) { $event->add('C'); };
                yield function (CollectingTask $event) { $event->add('R'); };
                yield function (CollectingTask $event) { $event->add('E'); };
                yield function (CollectingTask $event) { $event->add('L'); };
                yield function (CollectingTask $event) { $event->add('L'); };
            }
        };

        $p = new TaskProcessor($provider);

        $task = new CollectingTask();
        $p->process($task);

        $this->assertEquals('CRELL', implode($task->result()));
    }

    public function test_stoppable_events_stop() : void {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (StoppableCollectingTask $event) { $event->add('C'); };
                yield function (StoppableCollectingTask $event) { $event->add('R'); };
                yield function (StoppableCollectingTask $event) { $event->add('E'); $event->stopPropagation(); };
                yield function (StoppableCollectingTask $event) { $event->add('L'); };
                yield function (StoppableCollectingTask $event) { $event->add('L'); };
            }
        };

        $p = new TaskProcessor($provider);

        $task = new StoppableCollectingTask();
        $p->process($task);

        $this->assertEquals('CRE', implode($task->result()));
    }

    public function test_listener_exception_logged_and_notified() : void
    {
        $provider = new class implements ListenerProviderInterface {
            public function getListenersForEvent(EventInterface $event): iterable
            {
                yield function (CollectingTask $event) { $event->add('C'); };
                yield function (CollectingTask $event) { $event->add('R'); };
                yield function (CollectingTask $event) { throw new \Exception('Fail!'); };
                yield function (CollectingTask $event) { $event->add('L'); };
                yield function (CollectingTask $event) { $event->add('L'); };
            }
        };

        $errorHandlingNotifier = new class implements MessageNotifierInterface {
            /** @var array  */
            public $messages = [];

            public function notify(MessageInterface $message): void
            {
                $this->messages[] = $message;
            }
        };

        $p = new TaskProcessor($provider, $this->logger, $errorHandlingNotifier);

        $task = new CollectingTask();
        $p->process($task);

        $this->assertEquals('CR', implode($task->result()));

        $this->assertArrayHasKey(LogLevel::WARNING, $this->logger->messages);
        $this->assertCount(1, $this->logger->messages[LogLevel::WARNING]);
        $entry = $this->logger->messages[LogLevel::WARNING][0];
        $this->assertEquals('Unhandled exception thrown from listener while processing task.', $entry['message']);
        $this->assertEquals($task, $entry['context']['task']);

        $this->assertCount(1, $errorHandlingNotifier->messages);
        $this->assertInstanceOf(ErrorEvent::class, $errorHandlingNotifier->messages[0]);
    }
}
