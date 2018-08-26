<?php
declare(strict_types=1);

namespace Crell\Tukio;


use Crell\Tukio\OrderedCollection\OrderedCollection;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;

class OrderedCollectionBench
{

    /**
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchThing(): void
    {
        $collection = new OrderedCollection();

        $collection->addItem('a');
    }

}
