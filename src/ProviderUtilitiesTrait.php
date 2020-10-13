<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

trait ProviderUtilitiesTrait
{
    use ParameterDeriverTrait;

    protected function getAttributes(callable $listener): array
    {
        // Bail out < PHP 8.0.
        if (!class_exists('ReflectionAttribute', false)) {
            return [];
        }

        if ($this->isFunctionCallable($listener)) {
            $ref = new \ReflectionFunction($listener);
        }
        else if ($this->isClassCallable($listener)) {
            list($class, $method) = $listener;
            $ref = (new \ReflectionClass($class))->getMethod($method);
        }
        else if ($this->isObjectCallable($listener)) {
            list($class, $method) = $listener;
            $ref = (new \ReflectionObject($class))->getMethod($method);
        }

        if (!isset($ref)) {
            return [];
        }

        $attribs = $ref->getAttributes();

        return array_map(fn(\ReflectionAttribute $attrib) => $attrib->newInstance(), $attribs);
    }

    /**
     * Derives a predictable ID from the listener if possible.
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
