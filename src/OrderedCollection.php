<?php
declare(strict_types=1);

namespace Crell\Tukio;

/**
 * Defines an orderable collection of arbitrary values.
 *
 * Values may be added to the collection at any priority, or relative to an existing value.  When iterated they will
 * be returned in priority order with higher priority values being returned first.  The order in which values with the
 * same priority are returned is explicitly undefined and you should not rely on it.  (Although in practice is should be
 * FIFO, that is not guaranteed.)
 */
class OrderedCollection implements \IteratorAggregate
{

    /**
     * @var array
     *
     * An indexed array of arrays of Item entries. The key is the priority, the value is an array of Items.
     */
    protected $items;

    /**
     * @var Item[]
     *
     * A list of the items in the collection indexed by ID. Order is undefined.
     */
    protected $itemLookup;

    /**
     * @var bool
     */
    protected $sorted = false;

    /**
     * Adds an item to the collection with a given priority.  (Higher numbers come first.)
     *
     * @param $item
     *   The item to add. May be any data type.
     * @param int $priority
     *   The priority order of the item. Higher numbers will come first.
     * @return string
     *   An opaque ID string uniquely identifying the item for future reference.
     */
    public function addItem($item, int $priority = 0) : string
    {
        $id = uniqid('', true);

        $item = new Item($item, $priority, $id);

        $this->items[$priority][] = $item;
        $this->itemLookup[$id] = $item;

        $this->sorted = false;

        return $id;
    }

    /**
     * Adds an item to the collection before another existing item.
     *
     * Note: The new item is only guaranteed to get returned before the existing item. No guarantee is made
     * regarding when it will be returned relative to any other item.
     *
     * @param string $pivotId
     *   The existing ID of an item in the collection.
     * @param $item
     *   The new item to add.
     * @return string
     *   An opaque ID string uniquely identifying the new item for future reference.
     */
    public function addItemBefore(string $pivotId, $item) : string
    {
        if (!isset($this->itemLookup[$pivotId])) {
            throw new \InvalidArgumentException(sprintf('Cannot add item before undefined ID: %s', $pivotId));
        }

        // Because high numbers come first, we have to ADD one to get the new item to be returned first.
        return $this->addItem($item, $this->itemLookup[$pivotId]->priority + 1);
    }

    /**
     * Adds an item to the collection after another existing item.
     *
     * Note: The new item is only guaranteed to get returned after the existing item. No guarantee is made
     * regarding when it will be returned relative to any other item.
     *
     * @param string $pivotId
     *   The existing ID of an item in the collection.
     * @param $item
     *   The new item to add.
     * @return string
     *   An opaque ID string uniquely identifying the new item for future reference.
     */
    public function addItemAfter(string $pivotId, $item) : string
    {
        if (!isset($this->itemLookup[$pivotId])) {
            throw new \InvalidArgumentException(sprintf('Cannot add item after undefined ID: %s', $pivotId));
        }

        // Because high numbers come first, we have to SUBTRACT one to get the new item to be returned first.
        return $this->addItem($item, $this->itemLookup[$pivotId]->priority - 1);
    }

    /**
     * {@inheritdoc}
     * @return \ArrayIterator|\Traversable
     *
     * Note: Because of how iterator_to_array() works, you MUST pass `false` as the second parameter to that function
     * if calling it on the return from this object.  If not, only the last list's worth of values will be included in
     * the resulting array.
     */
    public function getIterator()
    {
        if (!$this->sorted) {
            krsort($this->items);
            $this->sorted = true;
        }

        return (function () {
            foreach ($this->items as $itemList) {
                yield from array_map(function(Item $item) {
                    return $item->item;
                }, $itemList);
            }
        })();
    }
}

class Item
{
    public function __construct($item, int $priority, string $id)
    {
        $this->item = $item;
        $this->priority = $priority;
        $this->id = $id;
    }

    public $item;

    public $priority;

    public $id;
}
