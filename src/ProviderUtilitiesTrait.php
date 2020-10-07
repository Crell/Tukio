<?php
declare(strict_types=1);

namespace Crell\Tukio;

use Fig\EventDispatcher\ParameterDeriverTrait;

trait ProviderUtilitiesTrait
{
    use ParameterDeriverTrait;

    protected function getAttributes(callable $listener): array
    {
        printf("In %s\n", __FUNCTION__);
        // Bail out < PHP 8.0.
        if (!class_exists('ReflectionAttribute', false)) {
            return [];
        }
        printf("ReflectionAttribute exists.\n");

        if ($this->isFunctionCallable($listener)) {
            printf("Function callable\n");
            $ref = new \ReflectionFunction($listener);
            printf("Function reflected\n");
        }
        else if ($this->isClassCallable($listener)) {
            list($class, $method) = $listener;
            $ref = (new \ReflectionClass($class))->getMethod($method);
        }
        else if ($this->isObjectCallable($listener)) {
            list($class, $method) = $listener;
            $ref = (new \ReflectionObject($class))->getMethod($method);
        }

        var_dump($ref);

        if (!isset($ref)) {
            return [];
        }
        printf("Got a ref\n");

        $attribs = $ref->getAttributes();

        var_dump($attribs);

        return array_map(fn(\ReflectionAttribute $attrib) => $attrib->newInstance(), $attribs);
    }

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
