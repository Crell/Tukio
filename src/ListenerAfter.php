<?php
declare(strict_types=1);

namespace Crell\Tukio;

use \Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class ListenerAfter implements ListenerAttribute
{
    public function __construct(
        public string $after,
        public ?string $id = null,
        public ?string $type = null,
    ) {}
}
