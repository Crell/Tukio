<?php

declare(strict_types=1);

namespace Crell\Tukio\Entry;

/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class ListenerStaticMethodEntry extends ListenerEntry implements CompileableListenerEntry
{
    public function __construct(
        public string $class,
        public string $method,
        public string $type,
    ) {}

    /**
     * @return array{
     *  entryType: string,
     *  type: string,
     *  class: ?string,
     *  method: ?string,
     * }
     */
    public function getProperties(): array
    {
        return [
            'entryType' => static::class,
            'class' => $this->class,
            'method' => $this->method,
            'type' => $this->type,
        ];
    }
}
