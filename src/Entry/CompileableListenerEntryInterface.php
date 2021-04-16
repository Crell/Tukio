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
     * @return array{
     *  entryType: string,
     *  type: string,
     *  listener: ?callable,
     *  class: ?string,
     *  serviceName: ?string,
     *  method: ?string,
     * }
     */
    public function getProperties(): array;
}
