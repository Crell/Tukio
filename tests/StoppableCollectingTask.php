<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Event\Dispatcher\StoppableEventInterface;

class StoppableCollectingEvent extends CollectingTask implements StoppableEventInterface
{
    use StoppableEventTrait;
}
