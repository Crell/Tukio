<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class ListenerPriority implements ListenerAttribute
{
    public ?Order $order = null;

    public function __construct(
        int $priority,
        public ?string $id = null,
        public ?string $type = null,
    ) {
        $this->order = Order::Priority($priority);
    }
}
