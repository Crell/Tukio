<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\MessageInterface;
use Psr\EventDispatcher\MessageNotifierInterface;
use Psr\EventDispatcher\StoppableTaskInterface;
use Psr\EventDispatcher\TaskInterface;
use Psr\EventDispatcher\TaskProcessorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class TaskProcessor implements TaskProcessorInterface
{
    /** @var ListenerProviderInterface  */
    protected $listeners;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(ListenerProviderInterface $listeners, LoggerInterface $logger = null)
    {
        $this->listeners = $listeners;
        $this->logger = $logger ?? new NullLogger();
    }

    public function process(TaskInterface $task): TaskInterface
    {
        foreach ($this->listeners->getListenersForEvent($task) as $listener) {
            try {
                $listener($task);
                if ($task instanceof StoppableTaskInterface && $task->isPropagationStopped()) {
                    break;
                }
            }
            // We do not catch Errors here, because Errors indicate the developer screwed up in
            // some way. Let those bubble up because they should just become fatals.
            catch (\Exception $e) {
                $this->logger->warning('Unhandled exception thrown from listener while processing task.', [
                    'task' => $task,
                    'exception' => $e,
                ]);

                throw $e;
            }
        }
        return $task;
    }
}
