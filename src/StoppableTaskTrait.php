<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\EventDispatcher\StoppableTaskInterface;

trait StoppableTaskTrait
{
    protected $stop = false;

    public function stopPropagation() : StoppableTaskInterface
    {
        $this->stop = true;
        return $this;
    }

    public function isStopped() : bool
    {
        return $this->stop;
    }
}
