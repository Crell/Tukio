<?php
declare(strict_types=1);

namespace Crell\Tukio;


class BasicStoppableTask extends BasicTask
{
    use StoppableEventTrait;
}
