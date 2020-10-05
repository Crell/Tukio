<?php
declare(strict_types=1);

namespace Crell\Tukio;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ListenerBefore
{
    public function __construct(
        public string $before,
        public ?string $id = null,
        public ?string $type = null,
    ) {}
}
