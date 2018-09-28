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
    /** @var string */
    public $serviceName;

    /** @var string */
    public $method;

    /** @var string */
    public $type;

    public function __construct(string $serviceName, string $method, string $type)
    {
        $this->serviceName = $serviceName;
        $this->method = $method;
        $this->type = $type;
    }

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
