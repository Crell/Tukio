<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

class StoppableCollectingEvent extends CollectingEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    public function stopPropagation() : self
    {
        $this->stopPropagation = true;
        return $this;
    }
}
