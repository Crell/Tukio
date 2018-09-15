<?php
declare(strict_types=1);

namespace Crell\Tukio\Entry;


interface CompileableListenerEntryInterface
{
    public function getProperties() : array;
}
