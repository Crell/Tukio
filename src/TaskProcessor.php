<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\MessageInterface;
use Psr\EventDispatcher\MessageNotifierInterface;
use Psr\EventDispatcher\TaskInterface;
use Psr\EventDispatcher\TaskProcessorInterface;
use Psr\EventDispatcher\StoppableTaskInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class TaskProcessor implements TaskProcessorInterface
{
    /** @var ListenerProviderInterface  */
    protected $listeners;

    /** @var LoggerInterface */
    protected $logger;

    /** @var MessageNotifierInterface */
    protected $notifier;

    public function __construct(ListenerProviderInterface $listeners, LoggerInterface $logger = null, MessageNotifierInterface $notifier = null)
    {
        $this->listeners = $listeners;
        $this->logger = $logger ?? new NullLogger();
        // Default to a null notifier implementation if not specified so we don't need to handle nulls later.
        $this->notifier = $notifier ?? new class implements MessageNotifierInterface {
                public function notify(MessageInterface $message): void {}
            };
    }

    public function process(TaskInterface $task): TaskInterface
    {
        foreach ($this->listeners->getListenersForEvent($task) as $listener) {
            try {
                $listener($task);
                if ($task instanceof StoppableTaskInterface && $task->isStopped()) {
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

                $errorMessage = new ErrorEvent($task, $e, $listener);
                $this->notifier->notify($errorMessage);

                // @todo I'm unclear if it's better to return the Task or rethrow the exception or what.
                // Discussion needed here as the behavior could be super different.
                // For instance, what if one implementation lets other listenrs keep going and another does not?
                // We should review this with the WG and answer it.
                return $task;
            }
        }
        return $task;
    }
}
