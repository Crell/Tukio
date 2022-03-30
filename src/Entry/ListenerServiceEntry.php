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
    public string $serviceName;

    public string $method;

    public string $type;

    public function __construct(string $serviceName, string $method, string $type)
    {
        $this->serviceName = $serviceName;
        $this->method = $method;
        $this->type = $type;
    }

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
