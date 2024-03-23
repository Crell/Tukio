<?php

namespace Crell\Tukio\Fakes;

class NotListenedDirectly implements EventParentInterface
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
