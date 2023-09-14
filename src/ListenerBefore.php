<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ListenerBefore extends Listener
{
    public function __construct(string $before, ?string $id = null, ?string $type = null) {
        parent::__construct(id: $id, before: $before, type: $type);
    }
}
