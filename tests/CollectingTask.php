<?php
declare(strict_types=1);

namespace Crell\Tukio;


class CollectingTask extends BasicTask
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
