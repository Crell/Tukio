<?php
declare(strict_types=1);

namespace Crell\Tukio;


class OrderedCollection implements \IteratorAggregate
{

    /**
     * @var array
     *
     * An indexed array of arrays of Item entries. The key is the priority, the value is an array of Items.
     */
    protected $items;

    public function addItem($item, int $priority = 0)
    {
        $id = uniqid('', true);

        $this->items[$priority][] = new Item($item, $priority, $id);
    }

    public function getIterator()
    {
        krsort($this->items);

        foreach ($this->items as $itemList) {
            $list = array_map(function(Item $item) {
                return $item->item;
            }, $itemList);
            foreach ($list as $i) {
                $return[] = $i;
            }
        }

        return new \ArrayIterator($return);

        /* This is the version that ought to work, but seems to not. Maybe a PHP bug?
        foreach ($this->items as $itemList) {
            $list = array_map(function(Item $item) {
                return $item->item;
            }, $itemList);
            yield from $list;
        }
        */
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
