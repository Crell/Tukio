<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Fig\EventDispatcher\StoppableTaskTrait;

class BasicStoppableTask extends BasicTask
{
    use StoppableTaskTrait;
}
