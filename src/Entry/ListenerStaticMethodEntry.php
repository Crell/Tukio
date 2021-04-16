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
    /** @var string */
    public $class;

    /** @var string */
    public $method;

    /** @var string */
    public $type;

    public function __construct(string $class, string $method, string $type)
    {
        $this->class = $class;
        $this->method = $method;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
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
