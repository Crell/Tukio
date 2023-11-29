<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ListenerAfter implements ListenerAttribute
{
    public ?Order $order = null;

    public function __construct(
        string $after,
        public ?string $id = null,
        public ?string $type = null,
    ) {
        $this->order = Order::After($after);
    }
}
