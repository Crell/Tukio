<?php

declare(strict_types=1);

namespace Crell\Tukio\Entry;

/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class ListenerStaticMethodEntry extends ListenerEntry implements CompileableListenerEntryInterface
{
    public string $class;

    public string $method;

    public string $type;

    public function __construct(string $class, string $method, string $type)
    {
        $this->class = $class;
        $this->method = $method;
        $this->type = $type;
        $this->listener = [$class, $method];
    }

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
