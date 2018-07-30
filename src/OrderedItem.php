<?php
declare(strict_types=1);

namespace Crell\Tukio;


/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class OrderedItem
{
    public $item;

    public $priority;

    public $id;

    public function __construct($item, int $priority, string $id)
    {
        $this->item = $item;
        $this->priority = $priority;
        $this->id = $id;
    }
}
