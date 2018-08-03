<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Psr\Event\Dispatcher\StoppableTaskInterface;

class StoppableCollectingTask extends CollectingTask implements StoppableTaskInterface
{
    use StoppableTaskTrait;
}
