<?php
declare(strict_types=1);

namespace Crell\Tukio;


class CollectingEvent
{
    protected $out = [];

    public function add(string $val) : void
    {
        $this->out[] = $val;
    }

    public function result() : array
    {
        return $this->out;
    }

}
