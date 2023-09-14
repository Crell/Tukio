<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;
use PHPUnit\Util\Exception;

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
        public ?int $priority = null,
        public ?string $before = null,
        public ?string $after = null,
        public ?string $type = null,
    ) {
        if (count(\array_filter([$before !== null, $after !== null, $priority !== null])) > 1) {
            throw new Exception('TODO: Make this a custom exception');
        }
    }

    /**
     * @internal
     */
    public function maskWith(?string $id = null, ?int $priority = null, ?string $before = null, ?string $after = null, ?string $type = null): self
    {
        return new self(
            id: $id ?? $this->id,
            priority: $priority ?? $this->priority,
            before: $before ?? $this->before,
            after: $after ?? $this->after,
            type: $type ?? $this->type,
        );
    }
}
