<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ListenerPriority implements ListenerAttribute
{
    public function __construct(
        public int $priority,
        public ?string $id = null,
        public ?string $type = null,
    ) {}
}
