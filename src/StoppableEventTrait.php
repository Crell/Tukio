<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\StoppableEventInterface;

trait StoppableEventTrait
{
    protected $stop = false;

    public function stopPropagation($stop = true) : StoppableEventInterface
    {
        $this->stop = $stop;
        return $this;
    }

    public function isStopped() : bool
    {
        return $this->stop;
    }
}
