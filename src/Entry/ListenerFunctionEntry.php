<?php
declare(strict_types=1);

namespace Crell\Tukio\Entry;

/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class ListenerFunctionEntry extends ListenerEntry implements CompileableListenerEntryInterface
{
    public function getProperties(): array
    {
        return [
            'entryType' => static::class,
            'listener' => $this->listener,
            'type' => $this->type,
        ];
    }

}
