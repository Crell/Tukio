<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ListenerPriority extends Listener
{
    public function __construct(?int $priority, ?string $id = null, ?string $type = null) {
        parent::__construct(id: $id, order: Order::Priority($priority), type: $type);
    }
}
