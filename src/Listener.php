<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

/**
 * The main attribute to customize a listener.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Listener implements ListenerAttribute
{
    /** @var string[]  */
    public array $before = [];

    /** @var string[] */
    public array $after = [];
    public ?int $priority = null;

    /**
     * @param ?string $id
     *     The identifier by which this listener should be known. If not specified one will be generated.
     * @param ?string $type
     *     The class or interface type of events for which this listener will be registered. If not provided
     *     it will be derived based on the type declaration of the listener.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $type = null,
    ) {}

    /**
     * @param array<ListenerBefore> $attribs
     */
    public function absorbBefore(array $attribs): void
    {
        foreach ($attribs as $attrib) {
            $this->id ??= $attrib->id;
            $this->type ??= $attrib->type;
            $this->before = [...$this->before, ...$attrib->before];
        }
    }

    /**
     * @param array<ListenerAfter> $attribs
     */
    public function absorbAfter(array $attribs): void
    {
        foreach ($attribs as $attrib) {
            $this->id ??= $attrib->id;
            $this->type ??= $attrib->type;
            $this->after = [...$this->after, ...$attrib->after];
        }
    }

    public function absorbPriority(ListenerPriority $attrib): void
    {
        $this->id ??= $attrib->id;
        $this->type ??= $attrib->type;
        $this->priority = $attrib->priority;
    }
}
