<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\OrderedCollection\OrderedCollection;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

/**
 * @Revs(1000)
 * @Iterations(3)
 * @Groups({"Collection"})
 */
class OrderedCollectionBench
{

    public function provideItems() : iterable
    {
        foreach ([1, 20, 50, 100, 500] as $count) {
            yield array_fill(1, $count, 'a');
        }
    }

    /**
     * @ParamProviders({"provideItems"})
     */
    public function bench_populate_ordered_collection(array $items): void
    {
        $collection = new OrderedCollection();

        foreach ($items as $item) {
            $collection->addItem($item);
        }
    }
}
