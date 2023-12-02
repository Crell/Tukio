<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ListenerAfter implements ListenerAttribute
{
    public array $after = [];

    public function __construct(
        string|array $after,
        public ?string $id = null,
        public ?string $type = null,
    ) {
        $this->after = is_array($after) ? $after : [$after];
    }
}
