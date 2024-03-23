<?php

declare(strict_types=1);

namespace Crell\Tukio\Events;


class CollectingEvent
{
    protected array $out = [];

    public function add(string $val): void
    {
        $this->out[] = $val;
    }

    public function result(): array
    {
        return $this->out;
    }

}
