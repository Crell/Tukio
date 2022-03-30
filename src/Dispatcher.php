<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Dispatcher implements EventDispatcherInterface
{
    protected ListenerProviderInterface $provider;

    protected LoggerInterface $logger;

    public function __construct(ListenerProviderInterface $provider, ?LoggerInterface $logger = null)
    {
        $this->provider = $provider;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event)
    {
        // If the event is already stopped, this method becomes a no-op.
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            // Technically this has an extraneous stopped-check after the last listener,
            // but that doesn't violate the spec since it's still technically checking
            // before each listener is called, given the check above.
            try {
                $listener($event);
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    break;
                }
            } // We do not catch Errors here, because Errors indicate the developer screwed up in
            // some way. Let those bubble up because they should just become fatals.
            catch (\Exception $e) {
                $this->logger->warning('Unhandled exception thrown from listener while processing event.', [
                    'event' => $event,
                    'exception' => $e,
                ]);

                throw $e;
            }
        }
        return $event;
    }
}
