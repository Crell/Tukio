<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\StoppableEventInterface;

class StoppableCollectingEvent extends CollectingEvent implements StoppableEventInterface
{
    protected bool $stopPropagation = false;

    public function isPropagationStopped() : bool
    {
        return $this->stopPropagation;
    }

    public function stopPropagation() : self
    {
        $this->stopPropagation = true;
        return $this;
    }
}
