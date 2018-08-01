<?php
declare(strict_types=1);

namespace Crell\Tukio;


use PHPUnit\Framework\TestCase;

class OrderedCollectionTest extends TestCase
{
    public function test_can_add_items_with_same_priority() : void
    {
        $c = new OrderedCollection();
        $c->addItem('A', 1);
        $c->addItem('B', 1);
        $c->addItem('C', 1);

        // Because the collection uses a generator in the getIterator() method, we have to explicitly ignore the
        // keys in iterator_to_array() or later values will overwrite earlier ones.
        $results = iterator_to_array($c, false);

        $this->assertEquals('ABC', implode($results));
    }

    public function test_can_add_items_with_different_priority() : void
    {
        $c = new OrderedCollection();
        // High priority number comes first.
        $c->addItem('C', 1);
        $c->addItem('B', 2);
        $c->addItem('A', 3);

        $results = iterator_to_array($c, false);

        $this->assertEquals('ABC', implode($results));
    }

    public function test_can_add_items_with_same_and_different_priority() : void
    {
        $c = new OrderedCollection();
        // High priority number comes first.
        $c->addItem('C', 2);
        $c->addItem('B', 3);
        $c->addItem('A', 4);
        $c->addItem('D', 1);
        $c->addItem('E', 1);
        $c->addItem('F', 1);

        $results = iterator_to_array($c, false);

        $this->assertEquals('ABCDEF', implode($results));
    }

    public function test_can_add_items_before_other_items() : void
    {
        $c = new OrderedCollection();
        // High priority number comes first.
        $cid = $c->addItem('C', 2);
        $c->addItem('D', 1);
        $c->addItem('A', 3);

        $c->addItemBefore($cid, 'B');

        $results = implode(iterator_to_array($c, false));

        $this->assertTrue(strpos($results, 'B') < strpos($results, 'C'));
    }

    public function test_can_add_items_after_other_items() : void
    {
        $c = new OrderedCollection();
        // High priority number comes first.
        $c->addItem('C', 2);
        $c->addItem('D', 1);
        $aid = $c->addItem('A', 3);

        $c->addItemAfter($aid, 'B');

        $results = implode(iterator_to_array($c, false));

        $this->assertTrue(strpos($results, 'B') > strpos($results, 'A'));
    }

    public function test_adding_before_non_existent_item_fails() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add item before undefined ID: a');

        $c = new OrderedCollection();

        // The values returned by `uniqid()` are always multiple characters long.
        $c->addItemBefore('a', 'B');
    }

    public function test_adding_after_non_existent_item_fails() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add item after undefined ID: a');

        $c = new OrderedCollection();

        // The values returned by `uniqid()` are always multiple characters long.
        $c->addItemAfter('a', 'B');
    }

    public function test_explicit_id_works() : void
    {
        $c = new OrderedCollection();
        $a = $c->addItem('A', 1, 'item_a');
        $c->addItemAfter('item_a', 'B');

        // Because the collection uses a generator in the getIterator() method, we have to explicitly ignore the
        // keys in iterator_to_array() or later values will overwrite earlier ones.
        $results = iterator_to_array($c, false);

        $this->assertEquals('AB', implode($results));
    }

    public function test_explicit_id_that_already_exists_works() : void
    {
        $c = new OrderedCollection();
        $a = $c->addItem('A', 1, 'an_item');
        $b = $c->addItem('B', 1, 'an_item');
        $c->addItemAfter($b, 'C');

        $this->assertNotEquals($a, $b);

        // Because the collection uses a generator in the getIterator() method, we have to explicitly ignore the
        // keys in iterator_to_array() or later values will overwrite earlier ones.
        $results = iterator_to_array($c, false);

        $this->assertEquals('ABC', implode($results));
    }
}
