<?php
declare(strict_types=1);

namespace Crell\Tukio\Benchmarks;


use Crell\Tukio\OrderedCollection\OrderedCollection;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;

/**
 * @Groups({"Collection"})
 */
class OrderedCollectionBench extends TukioBenchmarks
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
