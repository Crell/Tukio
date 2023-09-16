<?php

declare(strict_types=1);

namespace Crell\Tukio\Entry;

/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class ListenerServiceEntry implements CompileableListenerEntryInterface
{
    public function __construct(
        public string $serviceName,
        public string $method,
        public string $type,
    ) {}

    /**
     * @return array{
     *  entryType: string,
     *  type: string,
     *  serviceName: ?string,
     *  method: ?string,
     * }
     */
    public function getProperties(): array
    {
        return [
            'entryType' => static::class,
            'serviceName' => $this->serviceName,
            'method' => $this->method,
            'type' => $this->type,
        ];
    }
}
