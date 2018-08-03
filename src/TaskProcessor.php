<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Event\Dispatcher\TaskInterface;
use Psr\Event\Dispatcher\TaskProcessorInterface;
use Psr\Event\Dispatcher\StoppableTaskInterface;


class TaskProcessor implements TaskProcessorInterface
{
    /**
     * @var ListenerProviderInterface
     */
    protected $listeners;

    public function __construct(ListenerProviderInterface $listeners)
    {
        $this->listeners = $listeners;
    }

    public function process(TaskInterface $task): TaskInterface
    {
        foreach ($this->listeners->getListenersForEvent($task) as $listener) {
            $listener($task);
            if ($task instanceof StoppableTaskInterface && $task->isStopped()) {
                break;
            }
        }
        return $task;
    }
}
