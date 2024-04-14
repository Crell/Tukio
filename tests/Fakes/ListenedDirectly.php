<?php

namespace Crell\Tukio\Fakes;

class ListenedDirectly implements EventParentInterface
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
