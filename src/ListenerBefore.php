<?php
declare(strict_types=1);

namespace Crell\Tukio;

use \Attribute;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ListenerBefore implements ListenerAttribute
{
    public function __construct(
        public string $before,
        public ?string $id = null,
        public ?string $type = null,
    ) {}
}
