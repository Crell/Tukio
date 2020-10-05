<?php
declare(strict_types=1);

namespace Crell\Tukio;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ListenerAfter
{
    public function __construct(
        public string $after,
        public ?string $id = null,
        public ?string $type = null,
    ) {}
}
