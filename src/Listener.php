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
    public array $before = [];
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
            $this->before = [...$this->before, $attrib->order->before];
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
            $this->after = [...$this->after, $attrib->order->after];
        }
    }

    public function absorbPriority(ListenerPriority $attrib): void
    {
        $this->id ??= $attrib->id;
        $this->type ??= $attrib->type;
        $this->priority = $attrib->order->priority;
    }

    public function absorbOrder(Order $order): void
    {
        match (true) {
            $order instanceof OrderBefore => $this->before = [...$this->before, $order->before],
            $order instanceof OrderAfter => $this->after = [...$this->after, $order->after],
            $order instanceof OrderPriority => $this->priority = $order->priority,
            default => null,
        };
    }
}
