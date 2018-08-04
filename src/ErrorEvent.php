<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\EventErrorInterface;
use Psr\Event\Dispatcher\EventInterface;
use Psr\Event\Dispatcher\MessageInterface;

class ErrorEvent implements EventErrorInterface, MessageInterface
{
    /** @var EventInterface */
    protected $event;

    /** @var \Throwable */
    protected $throwable;

    /** @var callable */
    protected $listener;

    public function __construct(EventInterface $event, \Throwable $throwable, callable $listener)
    {
        $this->event = $event;
        $this->throwable = $throwable;
        $this->listener = $listener;
    }

    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }

    public function getListener(): callable
    {
        return $this->listener;
    }
}
