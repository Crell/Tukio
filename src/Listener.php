<?php
declare(strict_types=1);

namespace Crell\Tukio;

use \Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Listener implements ListenerAttribute
{
    public function __construct(
        public ?int $priority = 0,
        public ?string $id = null,
        public ?string $type = null,
    ) {}
}