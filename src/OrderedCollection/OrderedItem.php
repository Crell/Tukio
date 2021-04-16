<?php

declare(strict_types=1);

namespace Crell\Tukio\OrderedCollection;

/**
 * This is an internal class.  Do not use outside of this library.
 *
 * @internal
 */
class OrderedItem
{
    public $item;

    /** @var int */
    public $priority;

    /** @var string */
    public $id;

    /** @var string */
    public $before;

    /** @var string */
    public $after;

    public function __construct($item = null, int $priority = 0, string $id = '')
    {
        $this->item = $item;
        $this->priority = $priority;
        $this->id = $id;
    }

    public static function createWithPriority($item, int $priority, string $id) : self
    {
        $new = new static();
        $new->item = $item;
        $new->priority = $priority;
        $new->id = $id;

        return $new;
    }

    public static function createBefore($item, string $pivotId, $id) : self
    {
        $new = new static();
        $new->item = $item;
        $new->before = $pivotId;
        $new->id = $id;

        return $new;
    }

    public static function createAfter($item, string $pivotId, $id) : self
    {
        $new = new static();
        $new->item = $item;
        $new->after = $pivotId;
        $new->id = $id;

        return $new;
    }
}
