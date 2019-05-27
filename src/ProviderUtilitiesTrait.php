<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

trait ProviderUtilitiesTrait
{
    use ParameterDeriverTrait;

    /**
     * Derives a predictable ID from the listener if possible.
     *
     * @todo If we add support for annotations or similar for identifying listeners that logic would go here.
     *
     * It's OK for this method to return null, as OrderedCollection will generate a random
     * ID if necessary.  It will also handle duplicates for us.  This method is just a
     * suggestion.
     *
     * @param callable $listener
     *   The listener for which to derive an ID.
     * @return string|null
     *   The derived ID if possible or null if no reasonable ID could be derived.
     */
    protected function getListenerId(callable $listener) : ?string
    {

        if ($this->isFunctionCallable($listener)) {
            // Function callables are strings, so use that directly.
            return (string)$listener;
        }
        if ($this->isClassCallable($listener)) {
            return $listener[0] . '::' . $listener[1];
        }
        if ($this->isObjectCallable($listener)) {
            return get_class($listener[0]) . '::' . $listener[1];
        }

        // Anything else we can't derive an ID for logically.
        return null;
    }
}
