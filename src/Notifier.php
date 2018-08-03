<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\ListenerProviderInterface;
use Psr\Event\Dispatcher\MessageInterface;
use Psr\Event\Dispatcher\MessageNotifierInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Notifier implements MessageNotifierInterface
{
    /** @var ListenerProviderInterface  */
    protected $listeners;

    /** @var LoggerInterface|NullLogger  */
    protected $logger;

    public function __construct(ListenerProviderInterface $listeners, LoggerInterface $logger = null)
    {
        $this->listeners = $listeners;
        $this->logger = $logger ?? new NullLogger();
    }

    public function notify(MessageInterface $message): void
    {
        foreach ($this->listeners->getListenersForEvent($message) as $listener) {
            try {
                $listener(clone $message);
            }
            catch (\Throwable $e) {
                $this->logger->warning('Unhandled exception thrown from listener while processing message.', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            }
        }
    }
}
