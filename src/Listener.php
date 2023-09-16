<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

/**
 * The main attribute to customize a listener.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Listener implements ListenerAttribute
{
    /**
     * @param Order|null $order
     *     One of Order::Priority(), Order::Before(), or Order::After().
     * @param ?string $id
     *     The identifier by which this listener should be known. If not specified one will be generated.
     * @param ?string $type
     *     The class or interface type of events for which this listener will be registered. If not provided
     *     it will be derived based on the type declaration of the listener.
     */
    public function __construct(
        public ?string $id = null,
        public ?Order $order = null,
        public ?string $type = null,
    ) {}
}
