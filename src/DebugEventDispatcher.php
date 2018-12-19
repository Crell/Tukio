<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class DebugEventDispatcher implements EventDispatcherInterface
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * DebugEventDispatcher constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     *   The dispatcher to wrap and for which to log errors.
     * @param LoggerInterface $logger
     *   The logger service through which to log.
     */
    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public function dispatch(object $event)
    {
        $this->logger->debug('Processing event of type {type}.', ['type' => get_class($event), 'event' => $event]);
        return $this->dispatcher->dispatch($event);
    }
}
