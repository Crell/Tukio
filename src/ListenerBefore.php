<?php

declare(strict_types=1);

namespace Crell\Tukio;

use Attribute;
use Crell\AttributeUtils\Multivalue;

#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ListenerBefore implements ListenerAttribute, Multivalue
{
    /** @var string[] */
    public array $before = [];

    /**
     * @param string|array<string> $before
     */
    public function __construct(
        string|array $before,
        public ?string $id = null,
        public ?string $type = null,
    ) {
        $this->before = is_array($before) ? $before : [$before];
    }
}
