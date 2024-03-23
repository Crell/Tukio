<?php

declare(strict_types=1);

namespace Crell\Tukio\Entry;

/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class ListenerEntry
{
    /** @var callable */
    public $listener;

    public function __construct(callable $listener, public string $type)
    {
        $this->listener = $listener;
    }
}
