<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

/**
 * The main attribute to customize a listener.
 *
 * This attribute handles both priority sorting and topological (before/after)
 * sorting. For that reason, it MUST always be used with named arguments.
 * Specifying more than one of $priority, $before, or $after is an error.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Listener implements ListenerAttribute
{
    public function __construct(
        public ?string $id = null,
        public ?Order $order = null,
        public ?string $type = null,
    ) {}
}
