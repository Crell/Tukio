<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Event\Dispatcher\ModifyDispatcherInterface;
use Psr\Event\Dispatcher\StoppableEventInterface;


class BasicModifyDispatcher implements ModifyDispatcherInterface
{
    /**
     * @var ListenerProviderInterface
     */
    protected $listeners;

    public function __construct(ListenerProviderInterface $listeners)
    {
        $this->listeners = $listeners;
    }

    public function modify(EventInterface $event): EventInterface
    {
        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            $listener($event);
            if ($event instanceof StoppableEventInterface && $event->isStopped()) {
                break;
            }
        }
        return $event;
    }
}
