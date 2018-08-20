<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\StoppableTaskTrait;
use Psr\EventDispatcher\StoppableTaskInterface;

class StoppableCollectingTask extends CollectingTask implements StoppableTaskInterface
{
    use StoppableTaskTrait;
}
