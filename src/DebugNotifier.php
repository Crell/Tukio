<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\MessageInterface;
use Psr\EventDispatcher\MessageNotifierInterface;
use Psr\Log\LoggerInterface;

class DebugNotifier implements MessageNotifierInterface
{
    /** @var MessageNotifierInterface */
    protected $notifier;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * DebugNotifier constructor.
     *
     * @param MessageNotifierInterface $notifier
     *   The processor to wrap and for which to log errors.
     * @param LoggerInterface $logger
     *   The logger service through which to log.
     */
    public function __construct(MessageNotifierInterface $notifier, LoggerInterface $logger)
    {
        $this->notifier = $notifier;
        $this->logger = $logger;
    }

    public function notify(MessageInterface $message): void
    {
        $this->logger->debug('Notifying message of type {type}.', ['type' => get_class($message), 'message' => $message]);
        $this->notifier->notify($message);
    }
}
