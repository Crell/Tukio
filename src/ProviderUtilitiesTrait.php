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

        $attribs = $ref->getAttributes(ListenerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

        return array_map(function(\ReflectionAttribute $attrib) { return $attrib->newInstance(); }, $attribs);

        // Replace the above with this line once we require PHP 7.4.
        //return array_map(fn(\ReflectionAttribute $attrib) => $attrib->newInstance(), $attribs);
    }


    /**
     * Tries to get the type of a callable listener.
     *
     * If unable, throws an exception with information about the listener whose type could not be fetched.
     *
     * @param callable $listener
     * @return string
     */
    protected function getType(callable $listener)
    {
        try {
            $type = $this->getParameterType($listener);
        } catch (\InvalidArgumentException $exception) {
            if ($this->isClassCallable($listener) || $this->isObjectCallable($listener)) {
                throw InvalidTypeException::fromClassCallable($listener[0], $listener[1], $exception);
            }
            if ($this->isFunctionCallable($listener) || $this->isClosureCallable($listener)) {
                throw InvalidTypeException::fromFunctionCallable($listener, $exception);
            }
            throw new InvalidTypeException($exception);
        }
        return $type;
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
