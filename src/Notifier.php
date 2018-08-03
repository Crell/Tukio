<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Event\Dispatcher\MessageInterface;
use Psr\Event\Dispatcher\MessageNotifierInterface;

class Notifier implements MessageNotifierInterface
{
    /**
     * @var ListenerProviderInterface
     */
    protected $listeners;

    public function __construct(ListenerProviderInterface $listeners)
    {
        $this->listeners = $listeners;
    }

    public function notify(MessageInterface $message): void
    {
        foreach ($this->listeners->getListenersForEvent($message) as $listener) {
            $listener(clone $message);
        }
    }
}
