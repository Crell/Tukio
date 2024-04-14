<?php

namespace Crell\Tukio\Fakes;

interface EventParentInterface
{
    public function add(string $val): void;

    public function result(): array;
}
