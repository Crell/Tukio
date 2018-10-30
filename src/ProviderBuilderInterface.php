<?php
declare(strict_types=1);

namespace Crell\Tukio;

interface ProviderBuilderInterface extends \IteratorAggregate
{
    /**
     * Returns an iterable of listener entries, in the order they should be called if applicable.
     *
     * @return CompileableListenerEntryInterface[]
     */
    public function getIterator();
}
