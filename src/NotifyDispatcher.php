<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Event\Dispatcher\NotifyDispatcherInterface;

class NotifyDispatcher implements NotifyDispatcherInterface
{
    /**
     * @var ListenerProviderInterface
     */
    protected $listeners;

    public function __construct(ListenerProviderInterface $listeners)
    {
        $this->listeners = $listeners;
    }

    public function notify(EventInterface $event): void
    {
        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            $listener(clone $event);
        }
    }
}
