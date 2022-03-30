<?php

declare(strict_types=1);

namespace Crell\Tukio\Entry;

interface CompileableListenerEntryInterface
{
    /**
     * Extracts relevant information for the listener.
     *
     * @internal
     *
     */
    public function getProperties(): array;
}
