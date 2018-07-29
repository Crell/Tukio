<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Event\Dispatcher\EventInterface;

trait StoppableEventTrait
{
    protected $stop = false;

    public function stopPropagation(bool $stop = true) : EventInterface
    {
        $this->stop = $stop;
        return $this;
    }

    public function stopped() : bool
    {
        return $this->stop;
    }

}
